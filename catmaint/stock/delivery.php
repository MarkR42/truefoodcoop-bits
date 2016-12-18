<?php

require('db_inc.php');
require('html_table_reader_inc.php');
require('SpreadsheetReader.php');

$dbh = init_db_connection();

if (isset($_POST['process'])) {
    $delivery_data = do_process_stock();
    require('delivery.summary.inc.php');
    
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
    check_post_token();
    $supplier = $_POST['supplier'];
    $valid_codes = get_all_valid_product_codes($supplier);
    error_log("Number of valid codes=" . count($valid_codes));
    
    $fileinfo = $_FILES['file'];
    error_log("fileinfo name=" . $fileinfo['name']);
    error_log("fileinfo tmp_name=" . $fileinfo['tmp_name']);
    
    $is_html = (strpos(strtolower($fileinfo['name']), '.htm') != -1);
    
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
    error_log("Box quantity column is " . $column_box_quantity);
    $column_name = array_highest_index($widest_columns_by_column);
    error_log("Name column is " . $column_name);
    $column_product_code = array_highest_index($valid_codes_by_column);
    error_log("Product code column is " . $column_product_code);
    # Find quantity column.
    $column_quantity = find_column_by_name($sheet,
        Array('quantity picked', 'quantity', 'qty') );
    error_log("Quantity column is " . $column_quantity);
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

?>

