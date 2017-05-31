<?php

/*
 * Run this every day after the shop closes but before we take the
 * reporting data.
 * 
 * This script will do the following:
 * 
 * 1. Any NON-STOCK CONTROLLED items, we will set the stock level to
 * zero and delete its stock history.
 * 
 * This avoids these items screwing up any reports with phantom
 * (usually negative) stock.
 * 
 * Mostly Breads.
 * 
 * 2. Adjust the stock for multiple pack products.
 * 
 * These products have more than one SKU / reference, which is actually
 * the same item but in a different quantity.
 * 
 * For example, we have 6x 1l soya milks and 6x eggs, also 30 eggs.
 * 
 * 
 * Run this script with the following parameters:
 * 
 * db_host
 * db_name
 * db_username
 * db_password
 * 
 * in this order.
 * 
 */
 
if (isset($_SERVER['REQUEST_METHOD'])) {
    throw new Exception("This script must not be run as a web page");
}

$db_host = $argv[1];
$db_name = $argv[2];
$db_username = $argv[3];
$db_password = $argv[4];

error_reporting(E_ALL);

$dbh = new PDO("mysql:dbname=$db_name;host=$db_host", $db_username, $db_password);
# Make errors actually cause an exception.
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

zero_nonstock_items($dbh);

function do_zero_product($product_id)
{
    global $dbh;
    $location = '0'; # General Warehouse.
    # Update the current stock level 
    $st = $dbh->prepare("UPDATE stockcurrent SET UNITS=0 WHERE location=? AND product=?");
    $st->execute( Array($location, $product_id) );
    # Delete every stock movement (stockdiary) for this product.
    $st = $dbh->prepare("DELETE FROM stockdiary WHERE location=? AND product=?");
    $st->execute( Array($location, $product_id) );
}

function zero_nonstock_items($dbh)
{
    # Find breads: supplier AST
    $sql = "SELECT id, reference, name from products WHERE " .
        " reference like ?";
    $st = $dbh->prepare($sql);
    $st->execute( Array( '%AST' ) );
    $res = $st->fetchAll();
    foreach($res as $product) {
        $reference = $product['reference'];
        echo("Zeroing stock for $reference \n");
        do_zero_product($product['id']);
    }
    
}

            
?>
