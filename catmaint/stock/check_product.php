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

if (isset($_POST['set_reference'])) {
    do_set_reference();
}

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

function do_set_reference()
{
    global $dbh;
    check_post_token();

    $product_id = $_POST['select'];
    if (! $product_id) {
        throw Exception("Missing or falsy product_id");
    }
    $new_reference = $_POST['new_reference'];
    $st = $dbh->prepare("UPDATE products set REFERENCE=? WHERE ID=?");
    $st->execute(Array($new_reference, $product_id));
    header("Location: ./");
    exit(0);
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
<form method="POST">
<input type="hidden" name="dog" value="Spacey">
<input type="hidden" name="new_reference" value="<?php echo htmlspecialchars($unknown_code); ?>">
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
            <td>
                <label>
                    <input type="radio" name="select" value="<?php echo htmlspecialchars($row['id']) ?>">
                <?php echo htmlspecialchars($row['reference']) ?>
                </label>
                </td>
            <td><?php echo htmlspecialchars($row['catname']) ?></td>
            <td><?php echo htmlspecialchars($row['code']) ?></td>
            <td><?php echo htmlspecialchars($row['name']) ?></td>
        </tr>
    <?php 
        }
    ?>
    </tbody>
</table>
<p>
    <button type="submit" name="set_reference">SET REFERENCE for selected product</button>
        This will CHANGE the reference for the selected item in the catalogue, to match
        the reference shown above (<?php echo htmlspecialchars($unknown_code); ?> ). If
        this is obviously correct, then you can use this button, otherwise, do not.
</p>
</form>

<p><a href="./">Back to menu</a></p>
