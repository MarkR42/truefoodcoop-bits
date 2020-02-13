<?php

require('db_inc.php');

$dbh = init_db_connection();

function get_active_members()
{
    global $dbh;
    # Find customers who have ':' in their
    # notes, which means they are probably active members.
    $sql = "SELECT searchkey, card, name, notes FROM customers " .
        " WHERE notes like '%:%' " .
        " ORDER BY name";
    $st = $dbh->prepare($sql);
    $st->execute( Array(  ) );
    return $st->fetchAll();    
}

$active_members = get_active_members();

?>
<!DOCTYPE html>
<html>
<head>
<title>TFC Active members list</title>
<script src="js/JsBarcode.all.js"></script>
<style>
    /* Make margins disappear when printed */
@page 
{
    size:  auto;   /* auto is the initial value */
    margin: 0mm;  /* this affects the margin in the printer settings */
}
</style>
</head>
<body>
<?php if (! isset($_GET['print'])) { ?>
    <!-- preview, form etc. -->
<h1>TFC Active members list</h1>
<p>These are members who have Customer records in the system, 
because they have ever been registered.</p>
<form method="get">
<table>
<thead>
    <tr>
        <th>Card</th>
        <th>Name</th>
    </tr>
</thead>
<tbody>
<?php
    foreach ($active_members as $member) {
        ?>
        <tr>
            <td><canvas id="<?php echo htmlspecialchars($member['card']) ?>" 
                data-name="<?php echo htmlspecialchars($member['name']) ?>"
                ></canvas></td>
            <td>
                <?php echo htmlspecialchars($member['name']) ?>
            </td>
        </tr>
        <?php
    }
?>
</tbody>
</table>
<p><label>Name filter: <input type="text" name="namefilter"></label></p>
<p><label>Page offset: <input type="text" name="pageoffset" value="0"></label></p>
<p><input type="submit" name="print" value="PRINT"></p>
</form>
<p><a href="./">Back to menu</a></p>
<?php } else { ?>
<!-- PRINT -->

<?php
    $offset = (int) $_GET['pageoffset'];
    $namefilter = $_GET['namefilter'];
?>
<p>offset: <?php echo $offset ?> namefilter: <?php echo htmlspecialchars($namefilter) ?></p>
<?php
    $count = 0;
    foreach ($active_members as $member) {
        # Skip "pageoffset" members
        if ($offset > 0) {
            $offset -= 1;
            continue; 
        }
        $name = $member['name'];
        if ($namefilter) {
            # Ignore names which do not match.
            if (stristr($name, $namefilter) === FALSE) {
                continue;
            }
        }
        
        $cx = 105;
        $cy = 149;
        
        $card_width = 86;
        $card_height = 54;
        
        $col = ($count % 2);
        $row = (int) ($count / 2);
        
        $x = ($cx + ($col * $card_width) - ($card_width / 2) );
        $y = ($cy + ($row * $card_height) - ($card_height * 2) );
        
        $count += 1;
        ?>
<canvas id="<?php echo htmlspecialchars($member['card']) ?>" 
    data-name="<?php echo htmlspecialchars($member['name']) ?>"
    style="position:absolute; left: <?php echo $x ?>mm; top: <?php echo $y ?>mm"
></canvas>
        <?php
    }
    if ($count == 0) {
        echo("No matching members");
    }
?>

<?php } ?>
<script>
function dobarcodes()
{
    function pxtomm(n) {
        // Web browser "pixels" are 96 per inch?
        return (n / 96.0) * 25.4;
    }
    for (var e of document.getElementsByTagName("canvas")) {
        var cardid = e.id;
        var name = e.getAttribute("data-name");
        JsBarcode('#' + e.id, cardid, {'text':name, 'fontSize':12 });
        // THese are in pixels
        var w = e.width;
        var h = e.height;
        // These are in mm
        var w_mm = pxtomm(w);
        var h_mm = pxtomm(h);
        var newx = parseFloat(e.style.left) - (w_mm / 2);
        var newy = parseFloat(e.style.top) - (h_mm / 2);
        // Move the element to be centred.
        e.style.left = newx + 'mm';
        e.style.top = newy + 'mm';
        // e.style.border = "1px solid green";
    } 
}
window.addEventListener("load", dobarcodes);
</script>
</body>
</html>
