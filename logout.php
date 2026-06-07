<?php
session_start();
session_destroy();
header("Location: /codeguard/login.php");
exit();
?>
