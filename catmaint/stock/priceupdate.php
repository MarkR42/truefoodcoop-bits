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
} elseif (isset($_POST['checkrefs'])) {
    $check_data = do_check_references();
    require('checkrefs.summary.inc.php');
}else {
    // Show form.
    require('priceupdate.form.inc.php');
}


function get_current_prices($reference)
{
    # Return the current buy and sell price for a product.
    global $dbh;
    $sql = "SELECT reference, pricebuy, pricesell, taxes.rate as taxrate from " .
    " products p inner join taxes on p.taxcat = taxes.category AND validfrom < now() WHERE " .
        " reference = ? order by validfrom desc";
    $st = $dbh->prepare($sql);
    $st->execute( Array( $reference ) );
    $row = $st->fetch();
    if (! $row) {
        throw new Exception("Cannot get old price and tax for $reference");
    }
    
    return Array($row['pricebuy'], $row['pricesell'], $row['taxrate']);
}

function correct_sell_price($price)
{
    // change $price to a more desirable selling price, e.g.
    // exact number of pence.
    
    $round_pence = 1;
    // Over 1.50? Round to nearest 5p.
    if ($price > 1.49) {
        $round_pence = 5;
    }
    // 3 quid or more? Round to nearest 10p.
    if ($price > 2.99) {
        $round_pence = 10;
    }
    
    $pence = $price * 100;
    $pence = round($pence / $round_pence) * $round_pence;
    
    return ($pence / 100.0);
}

function price_is_close($p1, $p2)
{
    $p1_m = (int) ($p1 * 1000.0);
    $p2_m = (int) ($p2 * 1000.0);
    return ($p1_m == $p2_m);
}

function do_process_prices()
{
    global $dbh;
    check_post_token();
    $supplier = $_POST['supplier'];
    $markup_percent = (float) $_POST['markup'];
    $markup_factor = ($markup_percent + 100.0) / 100.0;
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
                # Get the previous values from the DB.
                list($old_price_buy, $old_price_sell, $taxrate) = get_current_prices($reference);
                
                $old_price_sell_inc = ($old_price_sell * (1.0 + $taxrate));
                
                # Calculate new sell price.
                $desired_sell_ex = $price_buy * $markup_factor;
                $desired_sell_inc = $desired_sell_ex * (1.0 + $taxrate);
                # Round / correct price.
                $desired_sell_inc = correct_sell_price($desired_sell_inc);
                # Recalculate sell price ex based on $desired_sell_inc
                $desired_sell_ex = $desired_sell_inc / (1.0 + $taxrate);
                
                # If the buy and sell prices are very close, then do nothing.
                $need_update = True;
                if (price_is_close($old_price_buy, $price_buy) and
                    price_is_close($desired_sell_inc, $old_price_sell_inc)) {
                    $need_update = False;
                }
                
                if ($need_update) {
                    $price_data[] = Array(
                        'reference' => $reference,
                        'product_code' => $product_code,
                        'box_quantity' => trim((string) $box_quantity),
                        'box_quantity_str' => $row[$column_box_quantity],
                        'price_buy' => $price_buy, 
                        'name' => trim($name),
                        'old_price_buy' => $old_price_buy,
                        'old_price_sell' => $old_price_sell,
                        'old_price_sell_inc' => $old_price_sell_inc,
                        'new_price_sell' => $desired_sell_ex,
                        'new_price_sell_inc' => $desired_sell_inc,
                    );
                }
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

function do_check_references()
{
    global $dbh;
    check_post_token();
    $supplier = $_POST['supplier'];
    $valid_codes = get_all_valid_product_codes($supplier,
        $_POST['categories']);
    
    $fileinfo = $_FILES['file'];

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

    # Scan the sheet to find various things.
    $valid_codes_by_column = Array();
    foreach ($sheet as $row) {
        for ($i=0; $i < count($row); $i++) {
            if (isset($valid_codes[$row[$i]])) {
                @ $valid_codes_by_column[$i] += 1;
            } 
        }
    }
    $column_product_code = array_highest_index($valid_codes_by_column);
    $codes_in_sheet = Array();
    // Missing codes:
    // Initially set all, then clear all the ones found in spreadsheet.
    $missing_codes = $valid_codes; // assoc array
    foreach ($sheet as $row) {
        unset($missing_codes[$row[$column_product_code]]);
    }
    $missing_codes_list = Array();
    foreach ($missing_codes as $code => $name) {
        $missing_codes_list[] = Array($code, $name);
    }
    sort($missing_codes_list);
    $check_data = Array(
        'missing_codes' => $missing_codes_list
    );
    return $check_data;
}
    
?>
