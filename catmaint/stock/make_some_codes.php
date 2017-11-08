<?php
    require('db_inc.php');

    $dbh = init_db_connection();

    require('ean13.php');
    
    $st = $dbh->prepare("SELECT MAX(CODE) FROM products WHERE CODE like '2902%' ");
    $st->execute();
    $row = $st->fetch();
    $code = $row[0];
    
    if (! $code) {
        $code = (int) '2902100008437'; # Fail?
    }
?>
<!DOCTYPE html>
<html>
<head>
<title>TFC Barcodes</title>
</head>
<body>

<h1>Barcodes</h1>
<p>The following codes are syntactically correct EAN13 codes and
not used (yet) in the system. They can be entered into the POS for
new products.</p>
<p>Be careful not to enter any spaces into the POS barcode field.</p>

<?php

    $separator = '<br>';
    
    for ($i=0; $i<10; $i++) {
        $code += rand(11,111);
        print(fix_ean_13($code));
        print($separator);
    }
    
?>

<p><a href="./">Back to menu</a></p>
    
</body>
