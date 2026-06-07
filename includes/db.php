<?php
define('DB_HOST', 'sql306.byethost8.com');
define('DB_USER', 'b8_41455270');
define('DB_PASS', '');
define('DB_NAME', 'b8_41455270_db1');


$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if(!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");
?>
