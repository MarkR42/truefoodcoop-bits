<?php

require('db_inc.php');
require('SpreadsheetReader.php');

$dbh = init_db_connection();

if (isset($_POST['process'])) {
    do_process_stock();
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
    global $dbh;
    $sql = "SELECT reference from products WHERE " .
        " reference like ?";
    $st = $dbh->prepare($sql);
    # Ends with ;; then supplier code
    $q = '%;;' . $supplier;
    $st->execute( [ $q ] );
    
    $valid_codes = Array();
    foreach ($st->fetchAll() as $row) {
        $reference = $row[0];
        # Strip off everything after ;
        $bits = explode(';', $reference);
        $code = strtolower($bits[0]);
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

function do_process_stock()
{
    check_post_token();
    $valid_codes = get_all_valid_product_codes($_POST['supplier']);
    error_log("Number of valid codes=" . count($valid_codes));
    
    $fileinfo = $_FILES['file'];
    error_log("fileinfo name=" . $fileinfo['name']);
    error_log("fileinfo tmp_name=" . $fileinfo['tmp_name']);
    $ssreader = new SpreadsheetReader($fileinfo['tmp_name'], $fileinfo['name']);
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
    foreach ($sheet as $row) {
        for ($i=0; $i < count($row); $i++) {
            if (parse_box_qty($row[$i])) {
                @ $box_qtys_by_column[$i] += 1;
            }
        }
        $widths = array_map("strlen", $row);
        $widest_index = array_highest_index($widths);
        @ $widest_columns_by_column[$widest_index] += 1;
    }
    $column_box_qty = array_highest_index($box_qtys_by_column);
    error_log("Box quantity column is " . $column_box_qty);
    $column_name = array_highest_index($widest_columns_by_column);
    error_log("Name column is " . $column_name);
    
    
    
    echo("OK SO FAR"); exit(1);
}

?>
<!DOCTYPE html>
<html>
<head>
<title>TFC Stock- Process Delivery</title>
</head>
<body>
<h1>TFC Stock- Process Delivery</h1>
<p>This page will process a delivery note.</p>
<!--


-->
<form METHOD="POST" enctype="multipart/form-data">
    <input type="hidden" name="dog" value="Spacey">
    <p>
        Supplier code <input size="5" maxlength="5" name="supplier" value="INF"> (generally 3 letters)
    </p>
    <p>
        File <input type="file" name="file"> - must be in 
            XLSX or HTML format.
    </p>

    <button  type="submit" name="process" value="YES">YES</button>
</form>
<p><a href="./">Back to menu</a></p>
    
</body>
