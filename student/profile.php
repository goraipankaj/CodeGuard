<?php
require_once '../includes/auth.php';
requireLogin();
$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id"));
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $newpass = trim($_POST['new_password']);

    if (!empty($newpass)) {
        $hashed = password_hash($newpass, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt,"sssi",$name,$email,$hashed,$user_id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=? WHERE id=?");
        mysqli_stmt_bind_param($stmt,"ssi",$name,$email,$user_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['name']  = $name;
        $_SESSION['email'] = $email;
        $success = "✅ Profile updated successfully!";
        $user['name']  = $name;
        $user['email'] = $email;
    } else {
        $error = "❌ Update failed! Try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile - CodeGuard</title>
<link rel="stylesheet" href="/codeguard/assets/css/style.css">
</head>
<body class="dash-body">
<?php include '../includes/header.php'; ?>
<div class="dashboard">
  <div class="sidebar">
    <div class="user-info">
      <div class="avatar"><?php echo strtoupper(substr($_SESSION['name'],0,2)); ?></div>
      <div>
        <div class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
        <div class="user-role">// student</div>
      </div>
    </div>
    <div class="sidebar-nav">
      <div class="sidebar-section-label">Menu</div>
      <a href="dashboard.php" class="nav-item">🏠 Dashboard</a>
      <a href="upload.php" class="nav-item">📤 Upload Code</a>
      <a href="submissions.php" class="nav-item">📁 My Submissions</a>
      <a href="results.php" class="nav-item">📊 My Results</a>
      <div class="sidebar-section-label">Account</div>
      <a href="profile.php" class="nav-item active">👤 Profile</a>
      <a href="/codeguard/logout.php" class="nav-item">🚪 Logout</a>
    </div>
  </div>
  <div class="main-content">
    <div class="page-title">My Profile</div>
    <div class="page-sub">Manage your account details</div>

    <?php if($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:820px;margin-top:20px">

      <!-- Profile Card -->
      <div class="panel" style="text-align:center;padding:36px">
        <div class="avatar" style="width:72px;height:72px;margin:0 auto 16px;font-size:24px;border-radius:20px">
          <?php echo strtoupper(substr($user['name'],0,2)); ?>
        </div>
        <div style="font-size:18px;font-weight:800">
          <?php echo htmlspecialchars($user['name']); ?>
        </div>
        <div style="color:var(--text2);font-size:13px;margin-top:6px">
          <?php echo htmlspecialchars($user['email']); ?>
        </div>
        <div style="margin-top:12px">
          <span class="chip">🎓 Student</span>
        </div>
        <div style="height:1px;background:var(--border);margin:20px 0"></div>
        <div style="font-size:12px;color:var(--text3)">
          Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
        </div>
      </div>

      <!-- Edit Form -->
      <div class="panel">
        <div class="panel-title">Edit Profile</div>
        <form method="POST" style="margin-top:16px">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name"
              value="<?php echo htmlspecialchars($user['name']); ?>" required>
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" nam
