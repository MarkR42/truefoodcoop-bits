<!DOCTYPE html>
<html>
<head>
<title>TFC Active members</title>
<style>
</style>
</head>
<body>
<h1>TFC Active members update</h1>
<p>For more information, please see this <a href="activemembers-example.xlsx">Example spreadsheet</a></p>

<form METHOD="POST" enctype="multipart/form-data">
    <input type="hidden" name="dog" value="Spacey">
    <p>
        YEAR <input size="5" maxlength="5" name="year" value="<?php echo $form_year ?>"> 
    </p>
    <p>
        MONTH <input size="5" maxlength="2" name="month" value="<?php echo $form_month ?>"> 
    </p>
    <p>
        File <input type="file" name="file"> - must be in 
            XLSX or CSV format. See example linked above.
    </p>
    <p>
        <button type="submit" name="update">Update active members</button>
    </p>

</form>
<p><a href="./">Back to menu</a></p>
</body>
