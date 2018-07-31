<?php

require('db_inc.php');

$dbh = init_db_connection();

$non_orphans = Array(); # Emulates a set
$product_counts = Array(); # Maps category id -> product count

# Get product counts and stick them in a big array.
function get_product_counts()
{
    global $dbh;
    global $product_counts;
    $st = $dbh->query(
        "SELECT category as category_id, count(*) as c FROM products " .
        " GROUP BY category ORDER BY category");
    foreach ($st->fetchAll() as $row) {
        $product_counts[$row['category_id']] = $row['c'];
    }
}

function print_category($category_id, $name) {
    global $product_counts;
    if (isset($product_counts[$category_id])) {
        $c = $product_counts[$category_id];
        $href = 'href="prodmaint.php?category_id=' . 
            htmlspecialchars($category_id) . '" ';
    } else {
        $c = 0;
        $href = '';
    }

    $aname = htmlspecialchars("c_" . $category_id);
    echo("<a name='$aname' $href>" . 
        htmlspecialchars($name). '</a>');
    echo(" (" . $c . ")");
}

function show_categories($parent_id)
{
    global $dbh;
    global $non_orphans;
    if ($parent_id) {
        $sql = "SELECT id, name from categories WHERE " .
            " parentid=? order by name";
        $st = $dbh->prepare($sql);
        $st->execute( Array( $parent_id ) );
    } else {
        $sql = "SELECT id, name from categories WHERE " .
            " parentid IS NULL order by name";
        $st = $dbh->prepare($sql);
        $st->execute( );
    }
    $res = $st->fetchAll();
    if (count($res) ) {
        echo("<ul>");
        foreach ($res as $row) {
            echo("<li>");
            print_category($row['id'], $row['name']);
            show_categories($row['id']);            
            $non_orphans[$row['id']] = 1; 
            echo("</li>");
        }
        echo("</ul>\n");
    }
}

function show_orphans()
{
    global $dbh;
    global $non_orphans;
    $sql = "SELECT id, name from categories " .
        " ORDER BY name";
    $st = $dbh->prepare($sql);
    $st->execute( );
    $res = $st->fetchAll();
    foreach ($res as $row) {
        if (! isset($non_orphans[$row['id'] ]) ) {
            echo("<li>");
            print_category($row['id'], $row['name']);
            echo("</li>");
        }
    }
}

get_product_counts();

?>
<!DOCTYPE html>
<html>
<head>
<title>TFC Stock- Category maintenance</title>
</head>
<body>
<h1>TFC Stock- Category maintenance</h1>
<h2>Main category tree</h2>
<p>Here will be a list of top-level and nested categories. These categories
    appear in the POS terminal.</p>
<div>
    <?php show_categories(null); ?>
</div>

<h2>Unlinked / orphan categories</h2>
<p>Other categories which are inaccessible from the root or POS terminal</p>
<p>These categories are either their own parent, or are descended
    from a category which is its own parent or have circular parents.</p>
<p>Usually these are "z-" obsolete categories only.</p>

<div>
        <ul>
        <?php show_orphans(); ?>
        </ul>
</div>

<p><a href="./">Back to menu</a></p>
    
</body>
