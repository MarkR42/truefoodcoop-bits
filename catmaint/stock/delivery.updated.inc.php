<?php

    # This is an include file.
    # $update_data should be:
    # Array of: Arrays (product_code, product_id, qty)
    
    $supplier = $_POST['supplier'];

?><!DOCTYPE html>
<html>
<head>
<title>TFC Stock- Stock update done.</title>
</head>
<body>
<h1>TFC Stock- Stock update done.</h1>

    <p>
        Supplier code <input size="5" maxlength="5" name="supplier" value="<?php echo htmlspecialchars($supplier) ?>">
    </p>
    <hr />
    <table>
        <thead>
            <tr>
                <th><div>Prodcode</div>
                    
                </th>
                <th>Total qty added</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                foreach ($update_data as $row) {
                    list($product_code, $product_id, $qty) = $row;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($product_code) ?></td>
                    <td class="numeric"><?php echo $qty ?></td>
                </tr>
            <?php
                } // end foreach 
            ?>
        </tbody>
    </table>
    
    <hr />
    <p>It is all done.</p>
<p><a href="./">Back to menu</a> </p>
    
</body>
