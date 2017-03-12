<?php

    # This is an include file.
    # $price_data should be:
    # Array of: Arrays, elements:
    #   product_code
    #   box_quantity
    #   name
    #   price_buy (unit price from supplier excluding tax)
    
    $supplier = $_POST['supplier'];
    $markup = (float) $_POST['markup'];

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

th {
    font-size: 80%;
}

.buyprice {
    background-color: #ddf;
}

.sellprice {
    background-color: #dfd;
}
.sellprice_ex {
    background-color: #efe;
}

input {
    margin-top: 0px;
    margin-bottom: 0px;
}

</style>
<body>
<h1>TFC Stock- Pricing update Summary</h1>

<form METHOD="POST">
    <input type="hidden" name="dog" value="Spacey">
    <p>
        Supplier code <input size="5" maxlength="5" name="supplier" value="<?php echo htmlspecialchars($supplier) ?>">
        Markup: <?php echo sprintf("%.1f", $markup) ?> %
    </p>
    <p>Select: <?php require('allornone.inc.php'); ?> </p>
    <hr />
    <table>
        <thead>
            <tr>
                <th colspan="3"><!-- nothing --></th>
                <th colspan="2">Buy prices</th>
                <th colspan="4">Sell prices</th>
            </tr>
            <tr>
                <th><div>Prodcode</div>
                    <div>(supplier's)</div>
                    
                </th>
                <th>concsize</th>
                <th>Units/case</th>
                <th class="buyprice">Old</th>
                <th class="buyprice">New</th>
                <th class="sellprice_ex">Old ex </th>
                <th class="sellprice">Old inc </th>
                <th class="sellprice_ex">New ex </th>
                <th class="sellprice">New inc </th>
                <th>%dif</th>
                <th>upd.</th>
                <th>Name of product (supplier's)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $counter = 1;
                foreach ($price_data as $row) {
                    $prod_reference = $row['product_code'] . ';;' . strtoupper($supplier);
                    $price_buy = $row['price_buy'];
                    $old_price_buy = $row['old_price_buy'];
                    $old_price_sell = $row['old_price_sell'];
                    $old_price_sell_inc = $row['old_price_sell_inc'];
                    $new_price_sell = $row['new_price_sell'];
                    $new_price_sell_inc = $row['new_price_sell_inc'];
                    $price_diff_pc = (($new_price_sell / $old_price_sell) * 100.0) - 100.0;
                    $cbid = "cb$counter"; # Checkbox ID
                    $counter += 1;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['product_code']) ?></td>
                    <td class="numeric"><?php echo htmlspecialchars($row['box_quantity_str']) ?></td>
                    <td class="numeric"><?php echo $row['box_quantity'] ?></td>
                    <td class="numeric buyprice">
                            <?php echo sprintf("%.3f", $old_price_buy) ?>
                    </td>
                    <td class="numeric buyprice">
                            <?php echo sprintf("%.3f", $price_buy) ?>
                    </td>
                    <td class="numeric sellprice_ex">
                            <?php echo sprintf("%.3f", $old_price_sell) ?>
                    </td>
                    <td class="numeric sellprice">
                            <?php echo sprintf("%.3f", $old_price_sell_inc) ?>
                    </td>
                    <td class="numeric sellprice_ex"><!-- new sell ex -->
                        <?php echo sprintf("%.3f", $new_price_sell) ?>
                    </td>
                    <td class="numeric sellprice"><!-- new sell inc -->
                        <?php echo sprintf("%.3f", $new_price_sell_inc) ?>                    
                    </td>
                    <td class="numeric">
                        <?php echo sprintf("%.1f", $price_diff_pc) ?>                    
                    </td>
                    <td>
                        <input type="hidden"
                            name="buyprice_<?php echo htmlspecialchars($row['reference']) ?>"
                            value="<?php echo $price_buy ?>"
                            >
                        <input type="hidden"
                            name="sellprice_<?php echo htmlspecialchars($row['reference']) ?>"
                            value="<?php echo $new_price_sell ?>"
                            >
                            
                        <input type="checkbox" 
                            name="update_sellprice[]"
                            value="<?php echo htmlspecialchars($row['reference'])?>"
                            id="<?php echo $cbid ?>"
                            >
                    </td>
                    <td>
                            <label for="<?php echo $cbid ?>">
                            <?php echo htmlspecialchars($row['name']) ?>
                            </label>
                                            
                    </td>
                </tr>
            <?php
                } // end foreach 
            ?>
        </tbody>
    </table>
    
    <hr />
    
<?php
    if ($counter > 1) {
?>
    <p>Check the above is correct, and select the checkboxes on
        desired products to update the sell price. If the box is not
        checked, then the price will not be changed.</p>
    <p>
        <button  type="submit" name="update" value="UPDATE">UPDATE PRICES</button>
    </p>
<?php
    } else {
?>
    <p>No matching products to update.</p>
<?php 
    } // end if.
?>
</form>
<p><a href="./">Back to menu</a> (cancel)</p>
    
</body>
