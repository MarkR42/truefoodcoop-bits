<?php

require('db_inc.php');

$dbh = init_db_connection();

$results = null;

# Check if we need to do some action:
if (isset($_POST['action'])) {
    check_post_token();
    $action = $_POST['action'];
    $product_id = $_POST['product_id'];
    if ($action == 'zero') {
        do_zero_product($product_id);
    } elseif ($action == 'delete') {
        do_delete_product($product_id);
    } else {
        throw Exception("Unknown action " . $action);
    }
}

if (isset($_GET['q'])) {
    # Do the search.
    $results = do_product_search($_GET['q']);
}


function do_product_search($q) {
    global $dbh;
    $q = trim($q); # Remove whitespace begin / end
    if ($q == '') {
        # Query is now empty.
        return [];
    }
    # Exact match, product ref
    $sql = "SELECT id, reference, name from products WHERE " .
        " reference=?";
    $st = $dbh->prepare($sql);
    $st->execute( [ $q ] );
    $res = $st->fetchAll();
    if (count($res) > 0) {
        return $res;
    }
    
    # Substring on name or reference.
    
    # Search for a substring match on the product name.
    $sql = "SELECT id, reference, name from products WHERE " .
        " NAME LIKE ? OR reference LIKE ? ORDER BY reference";
    $st = $dbh->prepare($sql);
    $like_str = '%' . $q . '%'; # Substring match using SQL LIKE
    $st->execute( [ $like_str, $like_str ] );
    return $st->fetchAll();
}

function do_zero_product($product_id)
{
    global $dbh;
    $location = '0'; # General Warehouse.
    # Update the current stock level 
    $st = $dbh->prepare("UPDATE stockcurrent SET UNITS=0 WHERE location=? AND product=?");
    $st->execute( [$location, $product_id] );
    # Delete every stock movement (stockdiary) for this product.
    $st = $dbh->prepare("DELETE FROM stockdiary WHERE location=? AND product=?");
    $st->execute( [$location, $product_id] );
    show_message("PRODUCT ZEROED"); 
    exit();
}

function do_delete_product($product_id)
{
    global $dbh;
    
    # Remove from products_cat
    $st = $dbh->prepare("DELETE FROM products_cat WHERE product=?");
    $st->execute( [$product_id] );
    # Stock tables:
    $st = $dbh->prepare("DELETE FROM stockcurrent WHERE product=?");
    $st->execute( [$product_id] );
    $st = $dbh->prepare("DELETE FROM stockdiary WHERE product=?");
    $st->execute( [$product_id] );
    $st = $dbh->prepare("DELETE FROM stocklevel WHERE product=?");
    $st->execute( [$product_id] );
    # Ticketlines: change to null.
    $st = $dbh->prepare("UPDATE ticketlines SET product=NULL where product=?");
    $st->execute( [$product_id] );
    # Master product table.
    $st = $dbh->prepare("DELETE FROM products WHERE id=?");
    $st->execute( [$product_id] );
    show_message("PRODUCT DELETED"); 
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
<title>TFC Stock- Product maintenance</title>
</head>
<body>
<h1>TFC Stock- Product maintenance</h1>
<h2>Search</h2>
<form method="GET">
    Product name, reference etc: 
    <input name="q" 
        value="<?php echo htmlspecialchars($_GET['q']) ?>"><!-- query -->
    <input type="submit" name="search">
</form>

<form method="POST">
    <input type="hidden" name="dog" value="Spacey">
<?php

if (isset($results)) {
?>
<table>
    <thead>
        <tr>
            <th colspan="2">Number of results: <?php echo count($results) ?></th>
        </tr>
        <tr>
            <th>Reference</th>
            <th>Name</th>
        </tr>
    </thead>
    <tbody>
<?php
    foreach($results as $product) {
?>
    <tr>
        <td>
            <label>
                <input type="radio" name="product_id" 
                    value="<?php echo htmlspecialchars($product['id']) ?>">
                <?php echo htmlspecialchars($product['reference']) ?>
            </label>
            </td>
        <td><?php echo htmlspecialchars($product['name']) ?></td>
    
    </tr>
<?php 
    } // end foreach
?>
</tbody></table>
<?php
    if (count($results) > 0) {
?>
<p>
    <button type="submit" name="action" value="zero">ZERO PRODUCT STOCK</button>
    <button type="submit" name="action" value="delete">DELETE PRODUCT</button>
</p>
<?php
    }
} // end if isset($results)
?>
</form>

<p><a href="./">Back to menu</a></p>
    
</body>