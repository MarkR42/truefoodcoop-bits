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
        <input size="5" maxlength="5" name="markup" value="33">
    </p>
    <p>
        File <input type="file" name="file"> - must be in 
            XLSX or HTML format.
    </p>

    <p>
        Categories... <button type="button" id="cats_all">All</button>
            <button type="button" id="cats_none">None</button>
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
    <button  type="submit" name="process" value="YES">YES</button>
    <span>(NB: There is a confirmation page. )</span>
</form>
<p><a href="./">Back to menu</a></p>

<script>
/*
 * Make the "check all" and "check none" buttons work...
 */
function check_all(on)
{
    var cbs = document.querySelectorAll("input[type=checkbox]");
    for (var i=0; i< cbs.length; i++) {
        cbs[i].checked = on;
    }
}

document.getElementById("cats_all").addEventListener("click",
    function() { check_all(true); } );
document.getElementById("cats_none").addEventListener("click",
    function() { check_all(false); } );
    

</script>    
</body>
