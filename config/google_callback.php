<?php
session_start();
require_once 'includes/db.php';

define('GOOGLE_CLIENT_ID',     '310584947355-7sj944b2pe15ovd39f5i8ro5af9drqvb.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'https://codeguard.byethost8.com/codeguard/google_callback.php');

// Step 1 — Get access token
if(!isset($_GET['code'])) {
    header("Location: login.php");
    exit();
}

$code = $_GET['code'];

// Exchange code for token
$token_url  = 'https://oauth2.googleapis.com/token';
$token_data = [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$token_response = curl_exec($ch);
curl_close($ch);

$token = json_decode($token_response, true);

if(!isset($token['access_token'])) {
    header("Location: login.php?error=token_failed");
    exit();
}

// Step 2 — Get user info
$user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $user_info_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token['access_token']]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$user_response = curl_exec($ch);
curl_close($ch);

$google_user = json_decode($user_response, true);

if(!isset($google_user['email'])) {
    header("Location: login.php?error=user_failed");
    exit();
}

$google_email = $google_user['email'];
$google_name  = $google_user['name'];

// Step 3 — Check user in DB
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email=?");
mysqli_stmt_bind_param($stmt, "s", $google_email);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if($user) {
    // Already registered — direct login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];
    header("Location: " . ($user['role']==='admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit();
} else {
    // New user — auto register as student
    $random_pass = password_hash(uniqid(), PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (name,email,password,role) VALUES (?,?,?,'student')");
    mysqli_stmt_bind_param($stmt, "sss", $google_name, $google_email, $random_pass);
    if(mysqli_stmt_execute($stmt)) {
        $new_id = mysqli_insert_id($conn);
        $_SESSION['user_id'] = $new_id;
        $_SESSION['name']    = $google_name;
        $_SESSION['email']   = $google_email;
        $_SESSION['role']    = 'student';
        header("Location: student/dashboard.php");
        exit();
    } else {
        header("Location: login.php?error=register_failed");
        exit();
    }
}
?>
