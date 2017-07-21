<?php
/*
 * Utility functions used in the true food coop
 * catalogue updater.
 */

function parse_box_qty($str) {
    # Examples:
    # 10kg
    # 25kg
    # 5l
    # 16.25kg
    # 10x250g
    # 10 x 250g
    # 10 X 250g
    # 6x(3x105ml)
    
    $str = strtolower($str);
    # remove all whitespace, even internal.
    $str = str_replace(' ', '', $str);
    # Remove brackets.
    $str = str_replace('(', '', $str);
    $str = str_replace(')', '', $str);
    
    list($boxqty, $itemsize) = sscanf($str, '%fx%f');
    if (isset($itemsize)) {
        return $boxqty;
    }
    
    # Whole units:
    # Try each "known suffix" and if we find it, then
    # return the numeric quantity.
    foreach (Array('kg','l') as $suffix) {
        if (str_ends_with($str, $suffix)) {
            return (float) $str;
        }
    }
    return FALSE;
}

function get_all_valid_product_codes($supplier, $categories = FALSE) 
{
    # Our product reference is e.g.
    # 3035;;INF                | Millet Flour gluten free 500g        
    # This function returns a dictionary of valid product codes
    # mapped to a true value
    # Codes will be mapped to upper case (if they contain letters)
    global $dbh;
    $sql = "SELECT reference, category, name from products WHERE " .
        " reference like ?";
    $st = $dbh->prepare($sql);
    # Ends with ;; then supplier code
    $q = '%;;' . $supplier;
    $st->execute( Array( $q ) );
    
    $valid_codes = Array();
    foreach ($st->fetchAll() as $row) {
        $reference = $row[0];
        $category = $row[1];
        $name = $row[2];
        if (! $name) {
            $name = 'Unnamed product'; # Ensure a true value.
        }
        if (! $categories or in_array($category, $categories)) {
            # Strip off everything after ;
            $bits = explode(';', $reference);
            $code = strtoupper($bits[0]);
            $valid_codes[$code] = $name;
        }
    }
    return $valid_codes;
}

function get_product_categories() 
{
    # Return an array of categories
    # with id and name.
    global $dbh;
    $sql = "SELECT id, name from categories WHERE " .
        " name not like 'z%' ORDER BY name";
    $st = $dbh->prepare($sql);
    $st->execute();
    return $st->fetchAll();
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

function read_spreadsheet_or_html($tmp_name, $name)
{    
    # Read $tmp_name (original name: $name)
    # If it looks like html, read it as html, otherwise use
    # the spreadsheet reader to read Excel file.
    $is_html = (strpos(strtolower($name), '.htm') !== FALSE);
    
    if ($is_html) {
        $ssreader = read_html_table($tmp_name);
    } else {
        $ssreader = new SpreadsheetReader($tmp_name, $name);
    }
    return $ssreader;
}

function str_ends_with($str, $suffix ) {
   return ( substr( $str, strlen( $str ) - strlen( $suffix ) ) === $suffix );
}

?>
