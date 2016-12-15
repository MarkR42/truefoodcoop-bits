<?php

require('db_inc.php');

if (isset($_POST['host'])) {
    // Form POST.
    // Assemble the cookie.
    $cookval = implode(':', 
        Array(   $_POST['host'], $_POST['database'], 
            $_POST['username'], $_POST['password']
        ));
    setcookie(TFC_COOKIE, $cookval);
    // Redirect back to the home page, hopefully it will be ok.
    header("Location: index.php"); 
    exit(0);
}


?>
<!DOCTYPE html>
<html>
<head>
<title>TFC Stock maintenance: enter database parameters</title>
</head>
<body>
<h1>Enter database parameters:</h1>
<?php
    if (isset($_GET['errmsg'])) {
        echo("<p>" . htmlspecialchars($_GET['errmsg']) . "</p>");
    }
?>
<form method="POST">
<p>
    HOST: <input type="text" name="host"> (the name or address of server)
</p>
<p>
    DATABASE: <input type="text" name="database" value="tfc_active_3">
</p>
<p>
    USERNAME: <input type="text" name="username" value="root">
</p>
<p>
    PASSWORD: <input type="text" name="password" value="">
</p>
<p><button type="submit">GO</button></p>

</form>
