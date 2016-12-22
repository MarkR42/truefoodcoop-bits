<?php

    # This is an include file.
    # $delivery_data should be:
    # Array of: Arrays, elements:
    #   product_code
    #   box_quantity
    #   quantity
    #   name
    
    $supplier = $_POST['supplier'];

?><!DOCTYPE html>
<html>
<head>
<title>TFC Stock- Delivery Summary</title>
</head>
<style type="text/css">
td {
    border-top: 1px solid black;
    border-bottom: 1px solid black;
    padding: 0.25em;
}

tr.error td {
    color:red;
    background-color: lightyellow;
}

td.numeric {
    text-align: right;
}

table {
    border-collapse: collapse;
}

input[type=number] {
    text-align: right;
    width: 5em;
    font-size: 125%;
}

</style>
<body>
<h1>TFC Stock- Delivery Summary</h1>

<form METHOD="POST">
    <input type="hidden" name="dog" value="Spacey">
    <p>
        Supplier code <input size="5" maxlength="5" name="supplier" value="<?php echo htmlspecialchars($supplier) ?>">
    </p>
    <hr />
    <table>
        <thead>
            <tr>
                <th><div>Prodcode</div>
                    <div>(supplier's)</div>
                    
                </th>
                <th>case x size</th>
                <th>Box size</th>
                <th><div>Quantity</div> 
                    <div>(of boxes)</div></th>
                <th>Total qty 
                    <div>(to add)</div></th>
                <th>Name of product (supplier's)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                foreach ($delivery_data as $row) {
                    $error = $row['error'];
                    $prod_reference = $row['product_code'] . ';;' . strtoupper($supplier);
                    $total_qty = $row['box_quantity'] * $row['quantity'];
            ?>
                <tr <?php if ($error) { echo 'class="error" '; } ?> >
                    <td><?php echo htmlspecialchars($row['product_code']) ?></td>
                    <td class="numeric"><?php echo htmlspecialchars($row['box_quantity_str']) ?></td>
                    <td class="numeric"><?php echo $row['box_quantity'] ?></td>
                    <td class="numeric"><?php echo $row['quantity'] ?></td>
                    <td>
                        <?php
                            if (! $error ) {
                        ?>
                            <input type="number" name="<?php echo htmlspecialchars($prod_reference) ?>"
                                value="<?php echo $total_qty ?>"
                                step="0.01"
                                >
                        <?php
                            }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['name']) ?>
                        <?php if ($error) { ?>
                            <div style="color: red">
                                <?php echo htmlspecialchars($error) ?>
                                <a href="check_product.php?code=<?php echo urlencode($prod_reference) ?>&amp;name=<?php echo urlencode($row['name']) ?>&amp;size=<?php echo urlencode($row['box_quantity_str']) ?>">CHECK PRODUCT</a>
                            </div>
                        <?php } ?>
                    </td>
                </tr>
            <?php
                } // end foreach 
            ?>
        </tbody>
    </table>
    
    <hr />
    
    <p>Check the above is correct, before pressing this button:</p>
    <p>
        <button  type="submit" name="update" value="YES">UPDATE STOCK</button>
    </p>
</form>
<p><a href="./">Back to menu</a> (cancel)</p>
    
</body>
