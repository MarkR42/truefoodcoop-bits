<?php

require('db_inc.php');
require('html_table_reader_inc.php');
require('SpreadsheetReader.php');
require('common_utils.php');

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

function do_process_stock()
{
    global $dbh;
    check_post_token();
    $supplier = $_POST['supplier'];
    $valid_codes = get_all_valid_product_codes($supplier);
    # error_log("Number of valid codes=" . count($valid_codes));
    
    $fileinfo = $_FILES['file'];
    tfc_log_to_db($dbh, "delivery: filename=" . $fileinfo['name']);
    
    $ssreader = read_spreadsheet_or_html($fileinfo['tmp_name'], $fileinfo['name']);

    # Filter out rows with too few columns.
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
    # error_log("rows: " . count($sheet));
    
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
        # Trim whitespace from all row
        $trimmed = array_map("trim", $row);
        # Remove any digit or punctuation, to avoid big timestamp
        # columns triggering the name detection.
        $chopped = Array();
        foreach ($trimmed as $item) {
            $chopped[] = preg_replace('/[\d,-:]/','',$item);
        }
        # Find widths after trimmed and chopped.
        $widths = array_map("strlen", $chopped);
        $widest_index = array_highest_index($widths);
        $widest_width = $widths[$widest_index];
        # In the case of very sparse rows, do not count.
        if ($widest_width > 2) {
            @ $widest_columns_by_column[$widest_index] += 1;
        }
    }
    $column_box_quantity = array_highest_index($box_qtys_by_column);
    tfc_log_to_db($dbh, "Box quantity column is " . $column_box_quantity);
    $column_name = array_highest_index($widest_columns_by_column);
    tfc_log_to_db($dbh, "Name column is " . $column_name);
    $column_product_code = array_highest_index($valid_codes_by_column);
    tfc_log_to_db($dbh, "Product code column is " . $column_product_code);
    # Find quantity column.
    $column_quantity = find_column_by_name($sheet,
        Array('quantity picked', 'quantity', 'qty', 'qnty') );
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
        # Default to 1 box quantity if unparseable.
        if (! $box_quantity) $box_quantity = 1;
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
        # If we have no record in stockcurrent, add one.
        $stockcurrent_st = $dbh->prepare("SELECT COUNT(*) FROM stockcurrent" .
            " WHERE product=? AND location=?");
        $stockcurrent_st->execute(Array($product_id, $location));
        $stockcurrent_row = $stockcurrent_st->fetch();
        if ($stockcurrent_row[0]) {
            # Stock already exists.
        } else {
            # Need to insert a new row.
            # With initially zero stock, we will immediately update it.
            $dbh->prepare("INSERT INTO stockcurrent (product, location, units) ".
                " VALUES (?,?,0)")
                ->execute(Array($product_id, $location));
        }
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

