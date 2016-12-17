<!DOCTYPE html>
<html>
<head>
<title>TFC Stock- Process Delivery</title>
</head>
<body>
<h1>TFC Stock- Process Delivery</h1>
<p>This page will process a delivery note.</p>

<form METHOD="POST" enctype="multipart/form-data">
    <input type="hidden" name="dog" value="Spacey">
    <p>
        Supplier code <input size="5" maxlength="5" name="supplier" value="INF"> (generally 3 letters)
    </p>
    <p>
        File <input type="file" name="file"> - must be in 
            XLSX or HTML format.
    </p>

    <button  type="submit" name="process" value="YES">YES</button>
</form>
<p><a href="./">Back to menu</a></p>
    
</body>
