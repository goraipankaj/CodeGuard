<?php
require_once '../includes/auth.php';
requireLogin();
$user_id = $_SESSION['user_id'];
$subs = mysqli_query($conn, "SELECT * FROM submissions WHERE user_id=$user_id ORDER BY upload_time DESC");
$total = mysqli_num_rows($subs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Submissions - CodeGuard</title>
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
      <a href="submissions.php" class="nav-item active">📁 My Submissions</a>
      <a href="results.php" class="nav-item">📊 My Results</a>
      <div class="sidebar-section-label">Account</div>
      <a href="profile.php" class="nav-item">👤 Profile</a>
      <a href="/codeguard/logout.php" class="nav-item">🚪 Logout</a>
    </div>
  </div>
  <div class="main-content">
    <div class="page-header">
      <div>
        <div class="page-title">My Submissions</div>
        <div class="page-sub">All your uploaded code files — <?php echo $total; ?> total</div>
      </div>
      <a href="upload.php" class="btn-primary" style="width:auto;padding:12px 24px">📤 Upload New</a>
    </div>

    <div class="panel">
      <?php if($total == 0): ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <p>No files uploaded yet!</p>
          <a href="upload.php" class="btn-primary" style="width:auto;display:inline-block;padding:12px 28px">📤 Upload First File</a>
        </div>
      <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>File Name</th>
            <th>Type</th>
            <th>Tokens</th>
            <th>Upload Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php $i=1; while($r = mysqli_fetch_assoc($subs)): ?>
          <tr>
            <td style="color:var(--text3)"><?php echo $i++; ?></td>
            <td class="mono" style="font-size:13px"><?php echo htmlspecialchars($r['file_name']); ?></td>
            <td><span class="file-tag"><?php echo strtoupper($r['file_type']); ?></span></td>
            <td class="mono"><?php echo $r['token_count']; ?></td>
            <td style="color:var(--text2);font-size:12px"><?php echo date('d M Y, H:i', strtotime($r['upload_time'])); ?></td>
            <td>
              <a href="results.php" class="btn-sm btn-view">📊 Results</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>
