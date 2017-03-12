<?php

    # This is an include file.
    # $reprice_info should have following attributes:
    # count_buyprices count_sellprices
    
    $supplier = $_POST['supplier'];
    $count_buyprices = $reprice_info['count_buyprices'];
    $count_sellprices = $reprice_info['count_sellprices'];

?><!DOCTYPE html>
<html>
<head>
<title>TFC Stock- Prices updated.</title>
</head>

<body>
<h1>TFC Stock- Prices updated.</h1>
<p>Supplier code <?php echo htmlspecialchars($supplier) ?></p>
<p>Buy prices updated count: <?php echo $count_buyprices ?></p>
<p>Sell prices updated count: <?php echo $count_sellprices ?></p>
<p><a href="./">Back to menu</a> finished.</p>
    
</body>
