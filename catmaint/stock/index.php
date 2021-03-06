<?php

require('db_inc.php');

$dbh = init_db_connection();

?>
<!DOCTYPE html>
<html>
<head>
<title>TFC Stock maintenance</title>
<script src="js/JsBarcode.all.js"></script>
</head>
<body>
<h1>TFC Stock maintenance</h1>
<p>Stock maintenance system is for authorised people only, 
if in doubt, please check with the shop manager or buyer.</p>
<p><a href="freshprices.php">Fruit and veg prices</a> - a quick
    way to update the prices of fresh produce.</p>
<p><a href="delivery.php">Upload delivery note</a>
    Take a supplier's delivery note, and add the stock listed.
    Needs a file upload.
</p>
<p><a href="priceupdate.php">Prices updater</a>
    Read a spreadsheet of supplier prices to update the prices
    in our system.
</p>
<hr />
<p><a href="make_some_codes.php">Make some barcodes</a>
    Create some barcode numbers in the correct format, which are
    not already used in the database. 
</p>
<h1>Active members</h1>
<p><a href="active_members.php">Active members update</a>
    This page sets up the discounts for next month's discounts.
</p>
<p><a href="active_members_list.php">Active members list</a>
<h2>Seldom needed</h2>
<p><a href="prodmaint.php">Product maintenance</a>
    Find a product and delete its stock or the whole product.
</p>
<p><a href="catmaint.php">Category maintenance</a>
    Move products between categories; see what products are in 
    each category.
</p>
<p><a href="zerostock.php">Zero stock</a>
    Use this only once per year, if you are just about to do a
    whole shop stock take!
</p>
<p>
<canvas id="bctest" width="300" height="60"></canvas>
</p>
</body>
