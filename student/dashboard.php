<?php
require_once '../includes/auth.php';
requireLogin();
$user_id = $_SESSION['user_id'];

// ✅ analysis_reports table use kar rahe hain
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM analysis_reports WHERE user_id=$user_id"))['c'];
$subs  = mysqli_query($conn, "SELECT * FROM analysis_reports WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Dashboard - CodeGuard</title>
<link rel="stylesheet" href="/codeguard/assets/css/style.css"></head>
<body class="dash-body">
<?php include '../includes/header.php'; ?>
<div class="dashboard">
  <div class="sidebar">
    <div class="user-info">
      <div class="avatar"><?php echo strtoupper(substr($_SESSION['name'],0,2)); ?></div>
      <div><div class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div><div class="user-role">Student</div></div>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item active">🏠 Dashboard</a>
      <a href="upload.php" class="nav-item">📤 Upload Code</a>
      <a href="submissions.php" class="nav-item">📁 My Submissions</a>
      <a href="/codeguard/logout.php" class="nav-item">🚪 Logout</a>
    </nav>
  </div>
  <div class="main-content">
    <h1 class="page-title">Student Dashboard</h1>
    <p class="page-sub">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>

    <div class="stats-grid">
      <div class="stat-card blue">
        <div class="stat-icon">📤</div>
        <div class="stat-value"><?php echo $total; ?></div>
        <div class="stat-label">Total Submissions</div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-title">Recent Submissions</div>
      <table class="data-table">
        <thead>
          <tr>
            <th>File Name</th>
            <th>Total Lines</th>
            <th>Similarity</th>
            <th>AI Score</th>
            <th>Uploaded</th>
          </tr>
        </thead>
        <tbody>
        <?php while($r = mysqli_fetch_assoc($subs)): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['filename']); ?></td>
            <td><?php echo $r['total_lines']; ?> lines</td>
            <td><?php
              $sim = round($r['similarity_percent'], 1);
              $color = $sim >= 70 ? '#ff4444' : ($sim >= 40 ? '#ff9800' : '#00c853');
              echo "<span style='color:$color;font-weight:700'>$sim%</span>";
            ?></td>
            <td><?php
              $ai = round($r['ai_score'], 1);
              $acolor = $ai >= 70 ? '#ff4444' : ($ai >= 40 ? '#ff9800' : '#00c853');
              echo "<span style='color:$acolor;font-weight:700'>$ai%</span>";
            ?></td>
            <td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
          </tr>
        <?php endwhile; ?>
        <?php if($total == 0): ?>
          <tr>
            <td colspan="5" style="text-align:center;padding:20px;color:#888">
              No submissions yet. 
              <a href="upload.php" style="color:var(--cyan)">Upload your first file!</a>
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div style="margin-top:20px">
      <a href="upload.php" class="btn-primary" 
         style="display:inline-block;width:auto;padding:12px 28px">
        📤 Upload New File
      </a>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
