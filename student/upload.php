<?php
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $code = '';
    $ext  = 'txt';
    $user_id = $_SESSION['user_id'];

    // ===== FILE OR TEXT =====
    if (!empty($_FILES['code_file']['tmp_name'])) {

        $file = $_FILES['code_file'];
        $allowed = ['php','c','cpp','java','txt'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "❌ Invalid file type!";
        } elseif ($file['size'] > 500000) {
            $error = "❌ File too large!";
        } else {

            $new_name = uniqid() . '_' . basename($file['name']);
            $dest = "../uploads/" . $new_name;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $code = file_get_contents($dest);
            } else {
                $error = "❌ Upload failed!";
            }
        }

    } elseif (!empty($_POST['code_text'])) {

        $code = $_POST['code_text'];
        $file['name'] = "manual_code.txt";

    } else {
        $error = "❌ Please upload or paste code!";
    }

    // ===== PROCESS =====
    if ($code && !$error) {

    // ===== TOKEN COUNT =====
    $tokens = ($ext === 'php') ? count(token_get_all($code)) : str_word_count($code);

    // ===== SAVE SUBMISSION =====
    $stmt = mysqli_prepare($conn, "INSERT INTO submissions (user_id,file_name,file_type,token_count) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt,"issi",$user_id,$file['name'],$ext,$tokens);

    if (mysqli_stmt_execute($stmt)) {

        // 🔥 ===== ADD THIS PART (IMPORTANT) =====

        // BASIC ANALYSIS
        $totalLines = count(explode("\n", $code));
        if($totalLines == 0) $totalLines = 1;

        $lines = explode("\n", $code);
        $dup = count($lines) - count(array_unique($lines));

        $copiedPercent = ($dup / $totalLines) * 100;
        $selfPercent = 100 - $copiedPercent;
        $commonPercent = 0;

        // SAVE ANALYSIS REPORT
        mysqli_query($conn, "INSERT INTO analysis_reports 
        (user_id, filename, total_lines, self_percent, common_percent, copied_percent) 
        VALUES 
        ('$user_id','".$file['name']."','$totalLines','$selfPercent','$commonPercent','$copiedPercent')");

        $success = "✅ Uploaded + Analyzed Successfully!";

    } else {
        $error = "❌ Database error!";
    }
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Upload Code - CodeGuard</title>
<link rel="stylesheet" href="/codeguard/assets/css/style.css">

<style>
.upload-zone:hover {
    border-color: #00e5ff;
    box-shadow: 0 0 15px rgba(0,229,255,0.2);
}
</style>

</head>

<body class="dash-body">

<?php include '../includes/header.php'; ?>

<div class="dashboard">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="user-info">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['name'],0,2)); ?></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                <div class="user-role">Student</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">🏠 Dashboard</a>
            <a href="upload.php" class="nav-item active">📤 Upload Code</a>
            <a href="submissions.php" class="nav-item">📁 My Submissions</a>
            <a href="/codeguard/logout.php" class="nav-item">🚪 Logout</a>
        </nav>
    </div>

    <!-- MAIN -->
    <div class="main-content">

        <h1 class="page-title">Upload Code</h1>
        <p class="page-sub">Upload or paste your code to analyze plagiarism</p>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- UPLOAD PANEL -->
        <div class="panel" style="
            max-width:600px;
            background:rgba(20,25,40,0.6);
            border:1px solid rgba(0,255,255,0.1);
            border-radius:15px;
            padding:30px;
            box-shadow:0 0 20px rgba(0,255,255,0.05);
        ">

            <form action="../analyze.php" method="POST" enctype="multipart/form-data">

                <!-- FILE UPLOAD -->
                <div class="upload-zone" onclick="document.getElementById('fi').click()" style="
                    border:2px dashed rgba(0,255,255,0.2);
                    border-radius:12px;
                    padding:40px;
                    text-align:center;
                    cursor:pointer;
                ">
                    <div style="font-size:40px;">📂</div>
                    <p style="color:#00e5ff;font-weight:600;">Click to upload file</p>
                    <p style="color:#7a9cc0;font-size:13px;">Supported: .php .c .cpp .java .txt</p>

                    <input type="file" id="fi" name="code_file" style="display:none"
                        onchange="document.getElementById('fileName').innerText=this.files[0].name">
                </div>

                <p id="fileName" style="margin-top:10px;color:#7a9cc0;font-size:13px;">
                    No file selected
                </p>

                <!-- OR -->
                <div style="text-align:center;margin:15px 0;color:#555;">OR</div>

                <!-- TEXT AREA -->
                <textarea name="code_text" placeholder="Paste your code here..."
                    style="
                    width:100%;
                    height:120px;
                    padding:10px;
                    border-radius:8px;
                    border:1px solid #1e293b;
                    background:#0c1220;
                    color:white;
                "></textarea>

                <!-- BUTTON -->
                <button type="submit" style="
                    width:100%;
                    margin-top:15px;
                    padding:12px;
                    background:linear-gradient(90deg,#2979ff,#00e5ff);
                    border:none;
                    border-radius:8px;
                    color:white;
                    font-weight:bold;
                    cursor:pointer;
                ">
                    🔍 Upload & Analyze
                </button>

            </form>

        </div>

    </div>

</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>
