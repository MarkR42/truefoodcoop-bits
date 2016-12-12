<?php

require('db_inc.php');

$dbh = init_db_connection();

?>
<!DOCTYPE html>
<html>
<head>
<title>TFC Stock maintenance</title>
</head>
<body>
<h1>TFC Stock maintenance</h1>
<p>Stock maintenance system is for authorised people only, 
if in doubt, please check with the shop manager or buyer.</p>
<p><a href="delivery.php">Upload delivery note</a>
    Take a supplier's delivery note, and add the stock listed.
    Needs a file upload.
</p>
<hr />
<p><a href="prodmaint.php">Product maintenance</a>
    Find a product and delete its stock or the whole product.
</p>
<p><a href="zerostock.php">Zero stock</a>
    Use this only once per year, if you are just about to do a
    whole shop stock take!
</p>

</body>
