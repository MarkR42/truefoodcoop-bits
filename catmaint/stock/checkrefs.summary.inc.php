<?php

    # This is an include file.
    # $check_data should have following attributes:
    # missing_codes: a list of product codes not found.
    
    $supplier = $_POST['supplier'];

?><!DOCTYPE html>
<html>
<head>
<title>TFC Stock- References check</title>
</head>

<body>
<h1>TFC Stock- References check</h1>
<h2>Missing codes</h2>
<p>These codes exist in the database but not the supplier price list.
    This usually means the product has been discontinued or it is a
    data error in our database.</p>
<ul>
    <?php 
        foreach ($check_data['missing_codes'] as $row) {
            $code = $row[0];
            $name = $row[1];
            $ref = $code . ";;" . $supplier;
            print("<li>" . htmlspecialchars($ref) . 
                " " .
                htmlspecialchars($name) .
                "</li>");
        }
    ?>

</ul>
<p><a href="./">Back to menu</a> (cancel)</p>
    
</body>
