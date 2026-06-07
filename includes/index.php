<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: /codeguard/admin/dashboard.php");
    } else {
        header("Location: /codeguard/student/dashboard.php");
    }
} else {
    header("Location: /codeguard/login.php");
}
exit();
?>
