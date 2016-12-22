<?php

/*
 * We will check the user has a cookie. If she does not, or if connecting
 * to the database failed, then prompt for info.
 * 
 * database host: hostname or ip address e.g. 192.168.2.10
 * database: (the mysql database name)
 * username: (the mysql username)
 * password (the mysql password)
 * 
 * Cookie format e.g.
 * 
 * $_COOKIE['tfc_db'] = '192.168.2.10:tfc_active_3:root:topsecret1'
 * 
 * They are stored separated by and assumed not to contain any embedded colon chars. 
 * 
 */

define("TFC_COOKIE", "tfc_db");

function init_db_connection()
{
    // Does user have a cookie?
    $cookval = $_COOKIE[TFC_COOKIE];
    $db_ok = false;
    if ($cookval) {
        $bits = explode(':', $cookval, 4);
        list($host, $database, $username, $password) = $bits;
        try { 
            $dbh = new PDO("mysql:dbname=$database;host=$host", $username, $password);
            # Make errors actually cause an exception.
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db_ok = true;
        } catch (PDOException $pe) {
            // Failed to connect to DB.
            $errmsg = (string) $pe->getMessage();
            error_log($pe);
        }
    }
    if (! $db_ok) {
        // Redirect to the page for database parameters.
        header("Location: db_params.php?errmsg=" . urlencode($errmsg));
        exit(0);
    }
    // Create table for the log
    $dbh->exec("
        CREATE TABLE IF NOT EXISTS tfc_log ( id integer not null primary key auto_increment,  
            ts datetime not null,
            message varchar(1024) NOT NULL
            )");
    return $dbh;
}

function check_post_token() {
    # Check for a "magic token" in a HTTP POST.
    # Throws exception if it is incorrect.
    if ($_POST['dog'] != 'Spacey') {
        throw Exception("Wrong dog");
    }
}

function show_message($msg) {
    echo("<p>" . htmlspecialchars($msg) . "</p>");
    echo('<p><a href="./">Product maintenance</a></p>');
}

function tfc_log_to_db($dbh, $msg) {
    $dbh->prepare("INSERT INTO tfc_log(ts, message) VALUES (NOW(), ?)")
        ->execute(Array($msg));
}


?>
