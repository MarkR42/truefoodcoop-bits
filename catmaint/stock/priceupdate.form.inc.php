<?php

$product_categories = get_product_categories();
?>

<!DOCTYPE html>
<html>
<head>
<title>TFC Stock- Prices update</title>
</head>
<body>
<h1>TFC Stock- Prices update</h1>
<p>This page will change the prices for a supplier based
    on a spreadsheet of the supplier's prices.</p>
<p>Do not use this when the shop is open.</p>

<form METHOD="POST" enctype="multipart/form-data">
    <input type="hidden" name="dog" value="Spacey">
    <p>
        Supplier code <input size="5" maxlength="5" name="supplier" value="INF"> (generally 3 letters)
    </p>
    <p>
        Markup (percent)
        <input size="5" maxlength="5" name="markup" value="35.0">
    </p>
    <p>
        File <input type="file" name="file"> - must be in 
            XLSX or HTML format.
    </p>

    <p>
        Categories... 
        <?php require('allornone.inc.php'); ?>
    </p>
    <p>
        <?php
            foreach ($product_categories as $prodcat) {
                # Default selection: everything except loose
                # (4.x loose)
        ?>
            <div>
            <label>
                <input type="checkbox" name="categories[]"
                    value="<?php echo htmlspecialchars($prodcat['id']) ?>"
                    <?php if ($prodcat['name'][0] != '4') { ?>
                        checked="checked"
                    <?php } ?>
                    
                    >
                    <?php echo htmlspecialchars($prodcat['name']) ?>
            </label>
            </div>
        <?php
            }
        ?>
    </p>
    <p>
    <button  type="submit" name="process" value="PROCESS">PROCESS</button>
    <span>(NB: There is a confirmation page. )</span>
    </p>
    <p>
    <button type="submit" name="checkrefs" value="Check Refs">Check References</button>
        do not update the prices - just check the references in the database.
    </p>
</form>
<p><a href="./">Back to menu</a></p>

</body>
