<?php
/*
 * This PHP is called when we have an unknown product.
 * 
 * Parameters passed:
 * 
 * code= product code, for example 671795;;INF
 * (This will not exist in the products table)
 * 
 * name= product name
 * 
 * size=8x120g (for example)
 * 
 * 
 * 
 */
require('db_inc.php');

$dbh = init_db_connection();

$unknown_code = $_GET['code'];
$unknown_name = $_GET['name'];
$unknown_size = $_GET['size']; #  Contains human-readable quantity.

$similar_products = find_similar_products($unknown_name);

function find_similar_products($name)
{
    global $dbh;
    # Keep chopping off bits from the end of $name until we
    # find something.
    $name_trunc = $name;
    while (strlen($name_trunc) > 4) { 
        $sql = "SELECT p.id, c.name AS catname, p.reference, p.code, p.name from products AS p " .
            " INNER JOIN categories AS c on p.CATEGORY = c.ID WHERE " .
            " p.name LIKE ? ORDER BY reference";
        $st = $dbh->prepare($sql);
        $q = '%' . $name_trunc . '%';
        $st->execute( Array( $q ) );
        $res = $st->fetchAll();
        if (count($res) > 0) {
            break;
        }
        $name_trunc = substr($name_trunc, 0, strlen($name_trunc) - 1);
    }
    return $res;
}


?>

<!DOCTYPE html>
<html>
<head>
<title>TFC Stock- Unknown product</title>
<p>This item wasn't found in the product database, it may be an error.
    If the entry was valid, then you should either fix a data error in
    the product database, or create a new product.</p>
<h2>Product name: <?php echo htmlspecialchars($unknown_name); ?></h2>
<h2>Product code: <?php echo htmlspecialchars($unknown_code); ?></h2>
<p>Size: <?php echo htmlspecialchars($unknown_size); ?> </p>
<h2>Similar products:</h2>
<table>
    <thead>
        <tr>
            <th>Reference</th>
            <th>Category</th>
            <th>Barcode</th>
            <th>Name</th>
        </tr>
    </thead>
    <tbody>
    <?php
        foreach ($similar_products as $row) {
    ?>
        <tr>
            <td><?php echo htmlspecialchars($row['reference']) ?></td>
            <td><?php echo htmlspecialchars($row['catname']) ?></td>
            <td><?php echo htmlspecialchars($row['code']) ?></td>
            <td><?php echo htmlspecialchars($row['name']) ?></td>
        </tr>
    <?php 
        }
    ?>
    </tbody>
</table>

<p><a href="./">Back to menu</a></p>
