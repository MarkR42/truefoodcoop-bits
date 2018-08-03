<?php

require('db_inc.php');

# Cookie name for default destination category (for move)
define('DEST_COOKIE', 'tfc_default_dest');

$dbh = init_db_connection();

$results = null;

# Check if we need to do some action:
if (isset($_POST['action'])) {
    check_post_token();
    $action = $_POST['action'];
    $product_ids = $_POST['product_ids'];
    $count = 0;
    foreach ($product_ids as $product_id) {
        $count += 1;
        if ($action == 'zero') {
            do_zero_product($product_id);
            $message = 'PRODUCTS ZEROED';
        } elseif ($action == 'delete') {
            do_delete_product($product_id);
            $message = 'PRODUCTS DELETED';
        } elseif ($action == 'move') {
            do_move_product($product_id, $_POST['dest_category']);
            $message = 'PRODUCTS MOVED';
        } else {
            throw new Exception("Unknown action " . $action);
        }
    }
    show_message("$count $message"); exit();
}

if (isset($_GET['q'])) {
    # Do the search.
    $results = do_product_search($_GET['q']);
    $category_search = false;
}

if (isset($_GET['category_id'])) {
    # Show products by category
    $results = find_products_by_category($_GET['category_id']);
    $st = $dbh->prepare("SELECT name from categories WHERE id=?");
    $st->execute(Array($_GET['category_id']));
    $stuff = $st->fetchAll();
    $category_search = $stuff[0][0];
}

$dest_categories = find_dest_categories();

function find_products_by_category($category_id)
{
    global $dbh;
    $sql = "SELECT p.id, p.reference, p.name, c.name AS catname from products AS p " .
        " inner join categories c on p.category = c.id WHERE " .
        " p.category=? ORDER BY p.name";
    $st = $dbh->prepare($sql);
    $st->execute( Array( $category_id ) );
    return $st->fetchAll();
}

function find_dest_categories()
{
    # Return result set for destination categories
    # (for move)
    global $dbh;
    $sql = "SELECT id, name FROM categories ORDER BY name";
    $res = $dbh->query($sql)->fetchAll();
    return $res;
}

function do_product_search($q) {
    global $dbh;
    $q = trim($q); # Remove whitespace begin / end
    if ($q == '') {
        # Query is now empty.
        return Array();
    }
    # Exact match, product ref
    $sql = "SELECT id, reference, name from products WHERE " .
        " reference=?";
    $st = $dbh->prepare($sql);
    $st->execute( Array( $q ) );
    $res = $st->fetchAll();
    if (count($res) > 0) {
        return $res;
    }
    
    # Substring on name or reference.
    
    # Search for a substring match on the product name.
    $sql = "SELECT p.id, p.reference, p.name, c.name AS catname from products AS p " .
        " inner join categories c on p.category = c.id WHERE " .
        " p.NAME LIKE ? OR p.reference LIKE ? ORDER BY p.reference";
    $st = $dbh->prepare($sql);
    $like_str = '%' . $q . '%'; # Substring match using SQL LIKE
    $st->execute( Array( $like_str, $like_str ) );
    return $st->fetchAll();
}

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

function do_delete_product($product_id)
{
    global $dbh;
    
    # Remove from products_cat
    $st = $dbh->prepare("DELETE FROM products_cat WHERE product=?");
    $st->execute( Array($product_id) );
    # Stock tables:
    $st = $dbh->prepare("DELETE FROM stockcurrent WHERE product=?");
    $st->execute( Array($product_id) );
    $st = $dbh->prepare("DELETE FROM stockdiary WHERE product=?");
    $st->execute( Array($product_id) );
    $st = $dbh->prepare("DELETE FROM stocklevel WHERE product=?");
    $st->execute( Array($product_id) );
    # Ticketlines: change to null.
    $st = $dbh->prepare("UPDATE ticketlines SET product=NULL where product=?");
    $st->execute( Array($product_id) );
    # Master product table.
    $st = $dbh->prepare("DELETE FROM products WHERE id=?");
    $st->execute( Array($product_id) );
}

function do_move_product($product_id, $dest_category_id) 
{
    global $dbh;
    # Move to new category
    $st = $dbh->prepare("UPDATE products SET category=? WHERE id=?");
    $st->execute( Array($dest_category_id, $product_id) );    
    global $done_a_move;
    if (! isset($done_a_move)) {
        $done_a_move = true;
        # Set a cookie, but only first time.
        $expire = time() + (3600 * 24);
        setcookie(DEST_COOKIE, $dest_category_id, $expire);
    }
}

?>
<!DOCTYPE html>
<html>
<head>
<title>TFC Stock- Product maintenance</title>
</head>
<body>
<h1>TFC Stock- Product maintenance</h1>
<?php

if ( $category_search ) {
    # Show category name heading;
?>
    <h2>Category: <?php echo htmlspecialchars($category_search); ?></h2>
<?php 
} else {
    # Display search form.
?>
    <h2>Search</h2>
    <form method="GET">
        Product name, reference etc: 
        <input name="q" 
            value="<?php echo htmlspecialchars($_GET['q']) ?>"><!-- query -->
        <input type="submit" name="search">
    </form>
<?php
} # end if ($category_search)
?>

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
            <th>Category</th>
            <th>Name</th>
        </tr>
    </thead>
    <tbody>
<?php
    foreach($results as $product) {
        $pid = $product['id'];
?>
    <tr>
        <td>
            <label>
                <input type="checkbox" name="product_ids[]" 
                    id="<?php echo htmlspecialchars($pid); ?>"
                    value="<?php echo htmlspecialchars($pid) ?>">
                <?php echo htmlspecialchars($product['reference']) ?>
            </label>
            </td>
        <td><?php echo htmlspecialchars($product['catname']) ?></td>
        <td style="padding-left: 1em">
            <label for="<?php echo htmlspecialchars($pid); ?>"><?php echo htmlspecialchars($product['name']) ?></label>
        </td>
    
    </tr>
<?php 
    } // end foreach
?>
</tbody></table>
<?php
    if (count($results) > 0) {
?>
<p>
    To category: 
    <select name="dest_category">
<?php
    $default_dest = $_COOKIE[DEST_COOKIE] or '';
    
    foreach ($dest_categories as $dest_category) {
        $cid = $dest_category['id'];
?>
        <option value="<?php echo htmlspecialchars($cid); ?>"
            <?php if ($cid == $default_dest) { echo(" selected=selected "); } ?>
            >
            <?php echo htmlspecialchars($dest_category['name']); ?>
        </option>    
<?php
    } # end foreach
?>
    </select>
    <button type="submit" name="action" value="move">Move selected products</button>
</p>
<p>
    <button type="submit" name="action" value="zero">ZERO PRODUCTS' STOCK</button>
    <button type="submit" name="action" value="delete">DELETE PRODUCTS</button>
</p>
<?php
    }
} // end if isset($results)
?>
</form>

<p><a href="./">Back to menu</a></p>
    
</body>
