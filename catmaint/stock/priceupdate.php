<?php

require('db_inc.php');
require('html_table_reader_inc.php');
require('SpreadsheetReader.php');
require('common_utils.php');

$dbh = init_db_connection();

if (isset($_POST['process'])) {
    // process the initial form.
    $price_data = do_process_prices();
    require('priceupdate.summary.inc.php');
} else {
    // Show form.
    require('priceupdate.form.inc.php');
}


function do_process_prices()
{
    global $dbh;
    check_post_token();
    $supplier = $_POST['supplier'];
    $valid_codes = get_all_valid_product_codes($supplier,
        $_POST['categories']);
    # error_log("Number of valid codes=" . count($valid_codes));
    
    $fileinfo = $_FILES['file'];
    tfc_log_to_db($dbh, "price update: filename=" . $fileinfo['name']);

    $ssreader = read_spreadsheet_or_html($fileinfo['tmp_name'], $fileinfo['name']);
    # Filter out rows with too few columns.
    $sheet = Array();
    foreach ($ssreader as $row) {
        # Check the number of columns. Minimum will be 4
        # (code, name, boxqty, price)
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
    # Find price column.
    $column_price = find_column_by_name($sheet,
        Array('price') );
    if ($column_price === FALSE) {
        $errors[] = log_and_return("Cannot find price column");
    }
    tfc_log_to_db($dbh, "Price column is " . $column_price);

    $price_data = Array();
    foreach ($sheet as $row) {
        $product_code = $row[$column_product_code];
        $product_code = trim((string) $product_code);
        $box_quantity = parse_box_qty($row[$column_box_quantity]);
        # Assume that anything we can't parse, is a single unit.
        if ($box_quantity === FALSE) {
            $box_quantity = 1;
        }
        $reference = $product_code . ';;' . $supplier;
        $box_quantity_override = read_box_quantity_override($reference);
        if ($box_quantity_override) {
            $box_quantity = $box_quantity_override;
        }
        $name = $row[$column_name];
        # Price
        $price = $row[$column_price];
        # Check price is sane.
        if (($price > 0.01) and ($price < 10000.0)) {
            # Ignore any unknown products, because the supplier
            # may stock a lot of additional things.
            if (isset($valid_codes[$product_code] )) {
                # Calculate unit buying price.
                $price_buy = ($price / $box_quantity);
                $price_data[] = Array(
                    'product_code' => $product_code,
                    'box_quantity' => trim((string) $box_quantity),
                    'box_quantity_str' => $row[$column_box_quantity],
                    'price_buy' => $price_buy, 
                    'name' => trim($name)
                );
            }
        }
    }
    $tmpfname = tempnam(sys_get_temp_dir(), 'tfcprice');
    $f = fopen($tmpfname, "w");
    if (! $f) { throw Exception("Unable to write temp file"); }
    fwrite($f, serialize($price_data));
    fclose($f);
    
    
    return $price_data;
}    
    
?>
