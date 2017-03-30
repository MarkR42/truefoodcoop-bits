<?php

require('db_inc.php');

$dbh = init_db_connection();

if (isset($_POST['zero'])) {
    check_post_token();
    zero_the_stock();
    exit();
}

function zero_the_stock() {
    global $dbh;
    $location = '0'; # General Warehouse.
    # Update the current stock level for all products, in this location
    # to zero.
    $st = $dbh->prepare("UPDATE stockcurrent SET UNITS=0 WHERE location=?");
    $st->execute( Array($location) );
    # Delete every stock movement (stockdiary) to/from this location.
    $st = $dbh->prepare("DELETE FROM stockdiary WHERE location=?");
    $st->execute( Array($location) );
    show_message("STOCK ZERO DONE"); 
}

?>
<!DOCTYPE html>
<html>
<head>
<title>TFC Stock- ZERO STOCK</title>
</head>
<body>
<h1>TFC Stock- ZERO STOCK</h1>
<p>This page will clear the whole stock in "General Warehouse". 
You should only do this if you are going to count all the stock in the
whole shop immediately afterwards!</p>
<form METHOD="POST"
    onsubmit="return confirm('Are you really, really sure?')">
    <input type="hidden" name="dog" value="Spacey">

    <button type="submit" name="zero" value="YES">YES, REALLY ZERO THE STOCK</button>
</form>
<p><a href="./">Back to menu</a></p>
    
</body>
