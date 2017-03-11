<?php

    # This is an include file.
    # $price_data should be:
    # Array of: Arrays, elements:
    #   product_code
    #   box_quantity
    #   name
    #   price_buy (unit price from supplier excluding tax)
    
    $supplier = $_POST['supplier'];

?><!DOCTYPE html>
<html>
<head>
<title>TFC Stock- Pricing update Summary</title>
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
<h1>TFC Stock- Pricing update Summary</h1>

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
                <th>Unit buy price 
                    </th>
                <th>Name of product (supplier's)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                foreach ($price_data as $row) {
                    $prod_reference = $row['product_code'] . ';;' . strtoupper($supplier);
                    $price_buy = $row['price_buy'];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['product_code']) ?></td>
                    <td class="numeric"><?php echo htmlspecialchars($row['box_quantity_str']) ?></td>
                    <td class="numeric"><?php echo $row['box_quantity'] ?></td>
                    <td class="numeric">
                            <?php echo sprintf("%.3f", $price_buy) ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['name']) ?>
                                            </td>
                </tr>
            <?php
                } // end foreach 
            ?>
        </tbody>
    </table>
    
    <hr />
    
    <p>Check the above is correct, FIXME. </p>
    <p>
        <button  type="submit" name="update" value="YES">UPDATE PRICES</button>
    </p>
</form>
<p><a href="./">Back to menu</a> (cancel)</p>
    
</body>
