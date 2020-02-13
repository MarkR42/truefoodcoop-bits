<?php

require('db_inc.php');
require('SpreadsheetReader.php');
require('common_utils.php');

$dbh = init_db_connection();
# Current dates for default form value
$current_datebits = getdate();
$current_year = $current_datebits['year'];
$current_month = $current_datebits['mon'];

$nextmonth_month = $current_month + 1;
$nextmonth_year = $current_year;

if ($nextmonth_month == 13) {
    $nextmonth_month = 1;
    $nextmonth_year += 1;
}

if (isset($_POST['year'])) {
    $form_year = ((int) $_POST['year']);
    $form_month = ((int) $_POST['month']);
    if (isset($_POST['update'])) {
        do_update_active_members();
    }
} else {
    $form_year = $nextmonth_year;
    $form_month = $nextmonth_month;
}

function update_member_discount($year, $month, $member_id, $member_name, $member_discount)
{
    global $dbh;
    # Create customer id if not already existing
    $card = sprintf("c%05d", $member_id);
    $dbh->prepare("insert IGNORE into customers (id, searchkey, name, card, visible, notes) ".
        " values (uuid(), ?, ?, ?, 1, 'YEAR-MONTH:DISCOUNTPERCENT')")
        ->execute(Array($member_id, $member_name, $card));
    # Get their notes
    $sql = "SELECT notes FROM customers WHERE " .
            " searchkey=?";
    $st = $dbh->prepare($sql);
    $st->execute( Array( $member_id ) );
    $row = $st->fetch();
    $notes = $row[0];
    
    # Make year-month combo
    $monthstr = sprintf("%04d-%02d", $year, $month);
    # Split notes into lines
    $lines = explode("\n", $notes);
    $lines_new = Array();
    foreach ($lines as $line) {
        # If it is for a different month, or something else,
        # leave it.
        if (substr($line,0, strlen($monthstr)) == $monthstr) {
            # Remove any for the same month.
        } else {
            if ($line != '') { # remove blank
                # keep
                $lines_new[] = $line;
            }
        }
    }
    $lines_new[] = $monthstr . ":" . $member_discount;
    $new_notes = join("\n", $lines_new);
    # Save updated notes.
    # error_log("New notes: " . $new_notes);
    $dbh->prepare("UPDATE customers SET notes=?, name=? WHERE searchkey=?")
        ->execute(Array($new_notes, $member_name, $member_id));

}

function do_update_active_members()
{
    global $dbh;
    check_post_token();
    $fileinfo = $_FILES['file'];
    $year = (int) $_POST['year'];
    $month = (int) $_POST['month'];
    
    $ssreader = new SpreadsheetReader($fileinfo['tmp_name'], $fileinfo['name']);
    # error_log("SSReader: " . print_r($fileinfo, true) );
    foreach ($ssreader as $row) {
        $member_id = (int) $row[0];
        if ($member_id > 0) {
            $member_name = $row[1];
            $member_discount = (int) $row[2];
            update_member_discount($year, $month, $member_id, $member_name, $member_discount);
        }
    }
    throw new Exception("TODO");
}
?>
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
