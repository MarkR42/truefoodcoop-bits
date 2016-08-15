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
}
table.main {
	height:100%;
	width:100%;
}

table.main tr {
	height: 14%;
}
table.main td {
	border: 1px solid black;
	padding: 0.5em;
	padding-left: 2em;
}

.code {
	margin-left: -1em;
	line-height: 50%;
}

.digits {
	font-size: 120%;
	font-family: monospace;
	line-height: 100%;
}

.pname {
	margin-top: 0.5em;
}



</style>
</head>
<body>
<table class="main">

<?php
	$total_labels = 14; // todo
	$labelnum = 0;
	while ($labelnum < $total_labels) {
		$qty = ($minqty + (floor($labelnum / $numlabels) * $incqty));
		if (($labelnum % 2) == 0) {
			if ($labelnum != 0) {
				print("</tr>");
			}
			print("<tr>");
		}
		$namestr = $pname . " (" . $qty . $qunit . ")";
		$code = $codeprefix . sprintf("%05d", $qty);
		$code = fix_ean_13($code);
?>
		<td>	
			<div class="code"><img src="barcode.php?text=<?php echo $code ?>" width="240" height="30"></div>
			<div class="digits">
				<?php echo $code; ?>
			</div>
			<div class="pname"><?php 
				echo htmlspecialchars($namestr)
				?></div>
		</td>
<?php
	$labelnum += 1;
	} // end while
?>
</table>
</body>
</html>
