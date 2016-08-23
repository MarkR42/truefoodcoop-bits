<?php

error_reporting(error_reporting() | E_ALL);

require("ean13.php");

// Get request parameters.

$pname = $_GET['pname'];
$codeprefix = $_GET['codeprefix']; // 7 digits of code.
$qunit = $_GET['qunit']; // Unit name e.g. g
$minqty = $_GET['minqty'];
$maxqty = $_GET['maxqty'];
$incqty = $_GET['incqty'];
$numlabels = $_GET['numlabels'];
$unitprice = (float) $_GET['unitprice'];
$extra = $_GET['extra'];

?>
<!DOCTYPE html>
<html>
<head>
<title>Barcode sheet - <?php echo htmlspecialchars($pname); ?></title>
<style>
html {
	height: 100%;
} 
body {
	height: 98%;
	margin: 0px;
    font-family: "Trebuchet MS", sans-serif;
}
table.main {
	height:100%;
	width:100%;
    border-collapse: collapse;
}

table.main tr {
	height: 14%;
}
table.main td {
	padding: 0.5em;
	padding-left: 2em;
    /* border: 1px solid grey; */
}

.code {
	margin-left: -1em;
	line-height: 70%;
}

.digits {
	font-size: 120%;
	font-family: monospace;
	line-height: 100%;
}

.pname {
	margin-top: 0.25em;
}

.extra {
    position: absolute;
    right: 1em;
    bottom: 1em;
    font-size: 75%;
    /* Will wrap, if needed. */
    max-width: 16em;
}

</style>
</head>
<body>
<table class="main">

<?php
	$total_labels = 14; // hard coded at 14 - the number per sheet.
	$labelnum = 0;
	while ($labelnum < $total_labels) {
		$qty = ($minqty + (floor($labelnum / $numlabels) * $incqty));
        $qty = min($qty, $maxqty);
		if (($labelnum % 2) == 0) {
			if ($labelnum != 0) {
				print("</tr>");
			}
			print("<tr>");
		}
		$namestr = $pname . " (" . $qty . $qunit . ")";
		$code = $codeprefix . sprintf("%05d", $qty);
		$code = fix_ean_13($code);
        # Calc price.
        $price_pence = ($unitprice * ($qty / 1000.0)) * 100;
        $price_pence = round($price_pence);
        $price = sprintf("%.2f", ($price_pence / 100));
?>
		<td style="position: relative">	
			<div class="code"><img src="barcode.php?text=<?php echo $code ?>" width="240" height="30"></div>
			<div class="digits">
				<?php echo $code; ?>
			</div>
			<div class="pname"><?php 
				echo htmlspecialchars($namestr)
				?></div>
            <?php if ($price > 0.01) { ?>
                <div>&pound;<?php echo $price ?></div>
            <?php } ?>
            <?php if ($extra) { ?>
                <div class="extra"><?php echo htmlspecialchars($extra) ?></div>
            <?php } ?>
		</td>
<?php
	$labelnum += 1;
	} // end while
?>
</table>
</body>
</html>
