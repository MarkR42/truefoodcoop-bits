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

/*
 * From MovementReason.java:
    public static final MovementReason IN_PURCHASE = new MovementReason(+1, "stock.in.purchase");
    public static final MovementReason IN_REFUND = new MovementReason(+2, "stock.in.refund");
    public static final MovementReason IN_MOVEMENT = new MovementReason(+4, "stock.in.movement");
    public static final MovementReason OUT_SALE = new MovementReason(-1, "stock.out.sale");
    public static final MovementReason OUT_REFUND = new MovementReason(-2, "stock.out.refund");
    public static final MovementReason OUT_BREAK = new MovementReason(-3, "stock.out.break");
    public static final MovementReason OUT_MOVEMENT = new MovementReason(-4, "stock.out.movement");
    
    public static final MovementReason OUT_CROSSING = new MovementReason(1000, "stock.out.crossing");
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
fixup_multiple_items($dbh);

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

function generate_stock_move($reference, $units)
{
    $location = '0';
    if ($units > 0) {
        $reason = 4;
    } else {
        $reason = -4;
    }
    
    global $dbh;
    echo("Adding $units to product $reference\n"); 
    # Update the stock
    $dbh->prepare("UPDATE stockcurrent SET units = units + ? " .
        " WHERE product=(select id from products where reference=?) AND location = ?")
            ->execute(Array($units, $reference, $location));
    # Create stock movement in diary.
    $dbh->prepare("INSERT INTO stockdiary (ID, datenew, reason, " .
        " location, product, units, price) VALUES (uuid(), NOW(),?," .
        " ?,(select id from products where reference=?),?, " .
        " (select pricebuy from products where reference=?) )")
        ->execute(Array( $reason,
            $location, $reference, $units, $reference));
}

function fixup_multiple_items($dbh)
{
    /*
     * Where we sell the same product in different sized cases, we have
     * >1 product in the system.
     * 
     * The rule is - we only keep stock levels for the *single* items.
     * 
     * Multiple cases are always logically broken into singles at end-of-day,
     * which means, that if we check IN multiple cases, they should be
     * broken out into singles, 
     * 
     * and if we *sell* multiple cases, the stock level will be negative,
     * so we should "replenish" the multiples by taking singles out.
     * 
     * 
     */
    $location = '0'; # General warehouse
    $sql = 'select reference, take_stock_from_reference, take_stock_quantity ' .
        ' FROM tfc_special_stock_rules WHERE take_stock_from_reference IS NOT NULL ' .
        ' ORDER BY reference';
    $dbh->beginTransaction();
    $st = $dbh->prepare($sql);
    $st->execute();
    $res = $st->fetchAll();
    foreach($res as $row) {
        $reference = $row['reference'];
        $take_stock_from_reference = $row['take_stock_from_reference'];
        $take_stock_quantity = $row['take_stock_quantity'];
        # Get current stock level.
        $sql = "select p.id, p.reference, sc.units FROM " .
            " products as p inner join stockcurrent as sc on sc.product = p.id " .
            " WHERE p.reference = ?";
        $st2 = $dbh->prepare($sql);
        $st2->execute( Array($reference));
        $row2 = $st2->fetch();
        $current_stock = 0;
        if ($row2) {
            $current_stock = $row2['units'];
        }
        echo("Stock level for $reference is $current_stock\n");
        if ($current_stock <> 0) {
            generate_stock_move($reference, - $current_stock);
            generate_stock_move($take_stock_from_reference, $current_stock * $take_stock_quantity);
        }
    }
    $dbh->commit();
}
            
?>
