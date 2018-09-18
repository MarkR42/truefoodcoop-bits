<?php

require('db_inc.php');

$dbh = init_db_connection();

# Define some reasonable bounds on prices.
define('PRICE_MIN', 0.10);
define('PRICE_MAX', 99.00); # Note that a few items are more than £10 / kg

$update_summary = FALSE;

if (isset($_POST['update'])) {
    check_post_token();
    # Handle POST.
    $update_summary = do_update_prices();
}

function format_price($price) {
    return sprintf("%.02f", $price);
}

function do_update_prices() {
    global $dbh;
    # POST PARAMETERS:
    # price[productid]
    # oldprice[productid]
    #
    # If price and oldprice are different (i.e. different amounts of pence)
    # (we should convert them to floats and check the difference)
    # (1.0 and 1.00 aren't different)
    #
    # IF price and oldprice are different, and the product id
    # exists and is contained in the fresh categories, then 
    # we update the price, and create summary data to display
    # to the user.
    $freshcats = find_fresh_categories(NULL);
    $freshset = Array(); # Keys are category IDs.
    foreach ($freshcats as $freshcat) {
        $freshset[$freshcat['id']] = 1;
    }
    
    # Loop through products
    $summary = array();
    foreach (array_keys($_POST['price']) as $pid) {
        # Get product info
        $sql = "select name, pricesell, category from products " .
            " WHERE id=?";
        $st = $dbh->prepare($sql);
        $st->execute( array($pid) );
        $product_row = $st->fetch();
        $product_category = $product_row['category'];
        
        if ( (! $product_category) or (! isset($freshset[$product_category] ) ) ) {
            throw new Exception("Product in wrong category: " . print_r($product_row, True));
        }
        # Check product price is really different
        $old_price = (float) $product_row['pricesell'];
        $new_price = (float) $_POST['price'][$pid];
        $prod_name = $product_row['name'];
        $diff = abs($old_price - $new_price);
        if ($diff < 0.005) {
            # trigger_error("Product price is very close to old price");
        } else {
            if (($new_price < PRICE_MIN) or ($new_price > PRICE_MAX)) {
                trigger_error("Product $pid price is unreasonable: $new_price");
            } else {
                # Actually do the update.
                $st = $dbh->prepare("UPDATE products set pricesell=? ".
                    " WHERE id=?");
                $st->execute(array($new_price, $pid) );                
                tfc_log_to_db($dbh, "Fresh price update: from $old_price to $new_price product: $pid $prod_name");
                $summary[] = array(
                    'id' => $pid,
                    'name' => $prod_name,
                    'old_price' => $old_price,
                    'new_price' => $new_price
                    );
            }
        }
    }
    return $summary;
}

function find_fresh_categories($parent_id)
{
    # Do an in-order traversal of the category tree, return it
    # flattened.
    $fresh_categories = Array();
    global $dbh;
    if ($parent_id) {
        # TODO: Find child categories
        $sql = "select c.id, c.name from categories AS c ".
            " WHERE parentid = ? ORDER BY name";
        $params = Array($parent_id);
    } else {
        # Find top-level fresh (fruit, veg) categories
        $sql = "select c.id, c.name from categories AS c " .
            " WHERE c.parentid IS NULL and ( " .
            " NAME like '%fruit%' or name like '%veg%'" .
            " ) order by name";
        $params = Array();
    }
    $st = $dbh->prepare($sql);
    $st->execute( $params );
    $res = Array();
    foreach ($st->fetchAll() as $row) {
        $res[] = $row;
        # Add children after parent
        foreach (find_fresh_categories($row['id']) as $childrow) {
            $res[] = $childrow;
        }
    }
    return $res;
}

function get_products($category_id)
{
    global $dbh;
    $sql = "select id, reference, name, pricesell, isscale FROM products " .
        " WHERE category=? ORDER BY name";
    $st = $dbh->prepare($sql);
    $st->execute( Array($category_id) );
    return $st->fetchAll();
}

$categories = find_fresh_categories(NULL);

?>
<!DOCTYPE html>
<html>
<head>
<title>TFC Stock- Fresh produce prices</title>
<style>
.prodname {
    display: inline-block;
    min-width: 30em;
    border-bottom: 1px solid black;
}
.odd {
    background-color: #dfd;
}
</style>
</head>
<body>
<h1>TFC Stock- Fresh produce prices</h1>

<?php
if (! $update_summary) {
?>
<form method="POST">
    <input type="hidden" name="dog" value="Spacey">

<p>This is a list of all fresh categories and products
    with updatable sale prices.</p>
<p>To modify product names, categories etc, use the EPOS desktop application.</p>
<div>
    <ul>
<?php
    foreach($categories as $category) 
    {
        $cid = $category['id'];
        $cname = $category['name'];
        $products = get_products($cid);
        if (count($products) > 0) {
?>
    <li><?php echo htmlspecialchars($cname) ?>
    
    <ul>
<?php
        $count = 0;
        foreach($products as $product) 
        {
            $count += 1;
            $odd = (($count % 2) != 0) ? "odd" : "";
            $pid = $product['id'];
            $pname = $product['name'];
            $price_str = format_price($product['pricesell']);
            $priceid = "price" . $pid;
?>
            <li>
                <label class="prodname <?php echo $odd ?>" for="<?php echo $priceid ?>">
                    <?php echo htmlspecialchars($pname) ?>
                </label>
                <input type="text" 
                    name="price[<?php echo $pid ?>]"
                    id="<?php echo $priceid ?>"
                    size=6 value="<?php echo $price_str ?>">
                <input type="hidden" 
                    name="oldprice[<?php echo $pid ?>]"
                    value="<?php echo $price_str ?>">
                <?php 
                    if ($product['isscale']) {
                        echo("/kg");
                    } else {
                        echo(" each");
                    }
                ?>
            </li>
<?php
        } # products
?>
    </ul></li>
<?php
    } // end if count(products) > 0
    } // end foreach categories
?>
</ul>
</div>
<div>
<button type="submit" name="update" value="1">UPDATE PRICES</button>

</div>
</form>
<?php
} else { # $update_summary contains update detials
?>    
<h2>Update summary:</h2>
<table border="border">
<thead>
    <tr>
        <th>Product</th>
        <th>Old price</th>
        <th>New price</th>
    </tr>
</thead>
<tbody>
<?php
    foreach ($update_summary as $update) {
?>
<tr>
    <td><?php echo htmlspecialchars($update['name']) ?></td>
    <td><?php echo format_price($update['old_price']) ?></td>
    <td><?php echo format_price($update['new_price']) ?></td>
</tr>
<?php
    }
?>
</tbody>
</table>
<?php
} # end if $update_summary
?>
<p><a href="./">Back to menu</a></p>
</body>
