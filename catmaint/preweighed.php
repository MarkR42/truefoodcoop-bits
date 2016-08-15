<html><head>

<title>TFC Preweighed barcode generator</title>
</head>
<body>
<h1>TFC Preweighed barcode generator</h1>
<p>For pre-weighed products: create the product in the EPOS database with the following parameters:</p>
<ul>
 <li>Product name: without quantity (Not: 200g etc)</li>
 <li>Barcode: Should be exactly 7 digits long, starting with digits 230. For example 2301234. Barcode must
	be unique.</li>
 <li>Price should be set per kilogram (or whole unit).</li>
</ul>

<form action="genpreweighed.php" method="get">
	<p>
	<label>Barcode prefix:<input type="text" name="codeprefix" size="7" maxlength="7"></label>
	<label>Product:<input type="text" name="pname" size="80"></label> (without units)</p>
	<p><label>Unit name (for example, "g" for grams)</label> <input type="text" name="qunit" size="6" value="g"></label> (must be 1/1000 of base unit, e.g. g=grams, or ml=millilitres)</p>
	<p>Quantities:</p>
	<ul>
		<li>Lower quantity (units) 
			<input type="numeric" name="minqty" value="200" size="6"></li>
		<li>Increment size (units)
			<input type="numeric" name="incqty" value="10" size="6"></li>
		<li>Maximum quantity (units)
			<input type="numeric" name="maxqty" value="200" size="6"></li>
		<li>Labels per quantity
			<input type="numeric" name="numlabels" value="1" size="4"></li>
	</ul>
	<p>Total number of labels generated will be ((maxqty - minqty) / incqty) - 1) * numlabels, with a maximum of
	14 labels per page.</p>
	<p><button type="submit">MAKE IT!</button>
</form>
