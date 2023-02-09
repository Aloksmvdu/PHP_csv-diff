<?php
require_once('csv_diff.php');
/*Header setting for json*/
header("Content-Type: application/json; charset=UTF-8");

$diff = new csv_diff("old.csv", "new.csv", "ID");
echo $diff->get_diff(PRINT_STYLE_JSON);
?>
