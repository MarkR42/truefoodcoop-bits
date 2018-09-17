<?php

require('db_inc.php');

$dbh = init_db_connection();

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
            $price_str = sprintf("%.02f", $product['pricesell']);
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
<p><a href="./">Back to menu</a></p>
</form>
    
</body>
