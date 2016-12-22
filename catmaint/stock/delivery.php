<?php

require('db_inc.php');
require('html_table_reader_inc.php');
require('SpreadsheetReader.php');

$dbh = init_db_connection();

if (isset($_POST['process'])) {
    $delivery_data = do_process_stock();
    require('delivery.summary.inc.php');

} elseif (isset($_POST['update'])) {
    $update_data = do_process_update();
    require('delivery.updated.inc.php');
} else {
    // Show form.
    require('delivery.form.inc.php');
}

function parse_box_qty($str) {
    # Examples:
    # 10kg
    # 25kg
    # 16.25kg
    # 10x250g
    # 10 x 250g
    # 10 X 250g
    
    $str = strtolower($str);
    # remove all whitespace, even internal.
    $str = str_replace(' ', '', $str);
    list($boxqty, $itemsize) = sscanf($str, '%fx%f');
    if (isset($itemsize)) {
        return $boxqty;
    }
    
    list($boxqty) = sscanf($str, '%fkg');
    $minus2 = substr($str, strlen($str) - 2);
    if (isset($boxqty) && ($minus2 == 'kg')) {
        return $boxqty;
    }
    return FALSE;
}

function get_all_valid_product_codes($supplier) 
{
    # Our product reference is e.g.
    # 3035;;INF                | Millet Flour gluten free 500g        
    # This function returns a dictionary of valid product codes
    # mapped to the value 1
    # Codes will be mapped to upper case (if they contain letters)
    global $dbh;
    $sql = "SELECT reference from products WHERE " .
        " reference like ?";
    $st = $dbh->prepare($sql);
    # Ends with ;; then supplier code
    $q = '%;;' . $supplier;
    $st->execute( Array( $q ) );
    
    $valid_codes = Array();
    foreach ($st->fetchAll() as $row) {
        $reference = $row[0];
        # Strip off everything after ;
        $bits = explode(';', $reference);
        $code = strtoupper($bits[0]);
        $valid_codes[$code] = 1;
    }
    return $valid_codes;
}

function log_and_return($str)
{
    error_log($str);
    return $str;
}

function array_highest_index($a)
{
    $highest = -100000;
    $highest_index = FALSE;
    
    foreach ($a as $k => $v) {
        if ($v > $highest) {
            $highest_index = $k;
            $highest = $v;
        }
    }
    return $highest_index;
}

function find_column_by_name(& $sheet, $name_list) 
{
    # Search for column headings from $name_list.
    # The first one found, will be returned.
    # If several appear on the same row, they will be
    # searched in order. So put $name_list in priority order.
    # Names are not case sensitive. $name_list must be in lower case.
    foreach ($sheet as $row) {
        $row_lc = array_map("strtolower", $row);
        $row_lc = array_map("trim", $row_lc);
        foreach ($name_list as $name) {
            $pos = array_search($name, $row_lc);
            if ($pos !== FALSE) {
                return $pos;
            }
        }
    }
    return FALSE; # Not found.
}

function read_box_quantity_override($reference)
{
    # Check if we have a box quantity override for this product,
    # and return it, or FALSE otherwise.
    global $dbh;
    $sql = "SELECT override_box_quantity from tfc_special_stock_rules WHERE " .
        " reference =? AND override_box_quantity IS NOT NULL";
    $st = $dbh->prepare($sql);
    $st->execute(Array($reference));
    $rows = $st->fetchAll();
    if (count($rows) > 0) {
        return $rows[0][0];
    } else {
        return FALSE;
    }
}

function do_process_stock()
{
    global $dbh;
    check_post_token();
    $supplier = $_POST['supplier'];
    $valid_codes = get_all_valid_product_codes($supplier);
    # error_log("Number of valid codes=" . count($valid_codes));
    
    $fileinfo = $_FILES['file'];
    tfc_log_to_db($dbh, "delivery: filename=" . $fileinfo['name']);
    
    $is_html = (strpos(strtolower($fileinfo['name']), '.htm') !== FALSE);
    
    if ($is_html) {
        $ssreader = read_html_table($fileinfo['tmp_name']);
    } else {
        $ssreader = new SpreadsheetReader($fileinfo['tmp_name'], $fileinfo['name']);
    }
    $sheet = Array();
    foreach ($ssreader as $row) {
        # Check the number of columns. Minimum will be 4
        # (code, name, boxqty, qty)
        if (count($row) >= 4) {
            $sheet[] = $row;
        }
    }
    $errors = Array();
    
    if (count($sheet) < 2) {
        $errors[] = log_and_return("Too few rows: " . count($sheet));
    }
    error_log("rows: " . count($sheet));
    
    # Scan the sheet to find various things.
    $box_qtys_by_column = Array();
    $widest_columns_by_column = Array();
    $valid_codes_by_column = Array();
    foreach ($sheet as $row) {
        for ($i=0; $i < count($row); $i++) {
            if (parse_box_qty($row[$i])) {
                @ $box_qtys_by_column[$i] += 1;
            }
            if (isset($valid_codes[$row[$i]])) {
                @ $valid_codes_by_column[$i] += 1;
            } 
        }
        $widths = array_map("strlen", $row);
        $widest_index = array_highest_index($widths);
        @ $widest_columns_by_column[$widest_index] += 1;
    }
    $column_box_quantity = array_highest_index($box_qtys_by_column);
    tfc_log_to_db($dbh, "Box quantity column is " . $column_box_quantity);
    $column_name = array_highest_index($widest_columns_by_column);
    tfc_log_to_db($dbh, "Name column is " . $column_name);
    $column_product_code = array_highest_index($valid_codes_by_column);
    tfc_log_to_db($dbh, "Product code column is " . $column_product_code);
    # Find quantity column.
    $column_quantity = find_column_by_name($sheet,
        Array('quantity picked', 'quantity', 'qty') );
    tfc_log_to_db($dbh, "Quantity column is " . $column_quantity);
    # Build a data structure:
    # Array of: Arrays, elements:
    #   product_code
    #   box_quantity
    #   quantity
    #   name
    $delivery_data = Array();
    foreach ($sheet as $row) {
        $product_code = $row[$column_product_code];
        $product_code = trim((string) $product_code);
        $box_quantity = parse_box_qty($row[$column_box_quantity]);
        $reference = $product_code . ';;' . $supplier;
        $box_quantity_override = read_box_quantity_override($reference);
        if ($box_quantity_override) {
            $box_quantity = $box_quantity_override;
        }
        $quantity = (float) $row[$column_quantity];
        $name = $row[$column_name];
        # Check that quantity is greater than zero
        if ($quantity > 0) {
            $error = FALSE;
            if (! isset($valid_codes[$product_code] )) {
                $error = "Product not found in database!";
            }
            
            $delivery_data[] = Array(
                'product_code' => $product_code,
                'box_quantity' => trim((string) $box_quantity),
                'box_quantity_str' => $row[$column_box_quantity],
                'quantity' => $quantity, # Might be not integer.
                'name' => trim($name),
                'error' => $error
            );
        }
    }
    
    $tmpfname = tempnam(sys_get_temp_dir(), 'tfc');
    $f = fopen($tmpfname, "w");
    if (! $f) { throw Exception("Unable to write temp file"); }
    fwrite($f, serialize($delivery_data));
    fclose($f);
    
    
    return $delivery_data;
}

function get_product_id_by_reference($reference) 
{
    global $dbh;
    $sql = "SELECT id from products WHERE " .
        " reference = ?";
    $sth = $dbh->prepare($sql);
    $sth-> execute(Array($reference));
    $row = $sth->fetch();
    if ($row === FALSE) {
        # Problem
        throw Exception("Product not found while updating " . $reference);
    }
    return $row[0];
}

function do_process_update()
{
    /*
     * User pressed button. Actually do the stock update. 
     */
    global $dbh;
    check_post_token();
    # iterate through the POST keys. If they contain a ; then they must
    # be product codes.
    $amounts_to_update = Array(); # Array of arrays (product_code, product_id, qty)
    foreach ($_POST as $key => $value) {
        if (strpos($key, ';') !== FALSE) {
            # Product code, from "reference" column.
            $product_id = get_product_id_by_reference($key);
            $qty = (float) $value;
            if ($qty > 0) {
                $amounts_to_update[] = Array($key, $product_id, $qty);
                tfc_log_to_db($dbh, "Found product: $key adding stock: $qty");
            }
        }
    }
    $location = '0'; # Stock location ID, from locations table.
    tfc_log_to_db($dbh, "Updating stocks...");
    $dbh->beginTransaction();
    foreach ($amounts_to_update as $amount) {
        list ($product_code, $product_id, $qty) = $amount;
        # Update the stock
        $dbh->prepare("UPDATE stockcurrent SET units = units + ? " .
            " WHERE product=? AND location = ?")
            ->execute(Array($qty, $product_id, $location));
        # Create stock movement in diary.
        $dbh->prepare("INSERT INTO stockdiary (ID, datenew, reason, " .
            " location, product, units, price) VALUES (uuid(), NOW(),?,?,?,?, ".
            " (select pricebuy from products where id=?) )")
            ->execute(Array( 1, # Reason 1 = incoming
                $location, $product_id, $qty, $product_id));
    }
    tfc_log_to_db($dbh, "Stock update complete.");
    $dbh->commit();
    return $amounts_to_update;
}
?>

