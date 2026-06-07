<?php
require_once 'includes/db.php';
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name=$_POST['name']; $email=$_POST['email']; $password=$_POST['password']; $role=$_POST['role'];
    if (empty($name)||empty($email)||empty($password)) {
        $error = "Please fill all fields!";
    } else {
        $stmt=mysqli_prepare($conn,"SELECT id FROM users WHERE email=?");
        mysqli_stmt_bind_param($stmt,"s",$email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt)>0) {
            $error="Email already registered!";
        } else {
            $hashed=password_hash($password,PASSWORD_DEFAULT);
            $stmt2=mysqli_prepare($conn,"INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)");
            mysqli_stmt_bind_param($stmt2,"ssss",$name,$email,$hashed,$role);
            if(mysqli_stmt_execute($stmt2)) $success="✅ Account created! <a href='login.php'>Login here</a>";
            else $error="Registration failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Register - CodeGuard</title>
<link rel="stylesheet" href="/codeguard/assets/css/style.css"></head>
<body class="auth-body">
<div class="auth-wrapper">
  <div class="auth-logo">
    <div class="logo-icon">⟨/⟩</div>
    <h1>CodeGuard</h1>
    <p>Code Plagiarism Detection System</p>
  </div>
  <div class="auth-card">
    <div class="auth-tabs">
      <a href="login.php" class="tab-btn">Login</a>
      <a href="register.php" class="tab-btn active">Register</a>
    </div>
    <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group"><label>Full Name</label><input type="text" name="name" placeholder="Your full name" required></div>
      <div class="form-group"><label>Email Address</label><input type="email" name="email" placeholder="student@college.edu" required></div>
      <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="Create password" required></div>
      <div class="form-group"><label>Role</label><select name="role"><option value="student">Student</option><option value="admin">Admin / Teacher</option></select></div>
      <button type="submit" class="btn-primary">→ Create Account</button>
    </form>
  </div>
</div>
</body></html>
