<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Analyzing... - CodeGuard</title>
<link rel="stylesheet" href="/codeguard/assets/css/style.css">
<style>
    .loading-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 80vh;
        text-align: center;
    }
    .shield {
        font-size: 80px;
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0%   { transform: scale(1);   opacity: 1; }
        50%  { transform: scale(1.1); opacity: 0.7; }
        100% { transform: scale(1);   opacity: 1; }
    }
    .loading-title {
        font-size: 28px;
        font-weight: 800;
        color: #00e5ff;
        margin: 20px 0 10px;
    }
    .loading-sub {
        color: #7a9cc0;
        font-size: 15px;
        margin-bottom: 40px;
    }
    .steps-wrap {
        width: 100%;
        max-width: 400px;
    }
    .step-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 20px;
        border-radius: 10px;
        margin-bottom: 10px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.05);
        color: #555;
        font-size: 14px;
        transition: all 0.4s ease;
    }
    .step-item.active {
        background: rgba(0,229,255,0.08);
        border-color: rgba(0,229,255,0.2);
        color: #00e5ff;
    }
    .step-item.done {
        background: rgba(0,200,83,0.08);
        border-color: rgba(0,200,83,0.2);
        color: #00c853;
    }
    .step-icon { font-size: 20px; width: 30px; text-align: center; }
    .spinner {
        width: 18px; height: 18px;
        border: 2px solid rgba(0,229,255,0.2);
        border-top-color: #00e5ff;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        flex-shrink: 0;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .progress-outer {
        width: 100%;
        max-width: 400px;
        height: 6px;
        background: rgba(255,255,255,0.05);
        border-radius: 10px;
        margin: 25px 0;
        overflow: hidden;
    }
    .progress-inner {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #2979ff, #00e5ff);
        border-radius: 10px;
        transition: width 0.5s ease;
    }
</style>
</head>
<body class="dash-body">
<?php include '../includes/header.php'; ?>

<div class="dashboard">
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

    <div class="main-content">
        <div class="loading-wrap">

            <div class="shield">🛡️</div>
            <div class="loading-title">Analyzing Your Code...</div>
            <div class="loading-sub">Please wait while CodeGuard checks your submission</div>

            <!-- Progress Bar -->
            <div class="progress-outer">
                <div class="progress-inner" id="progressBar"></div>
            </div>

            <!-- Steps -->
            <div class="steps-wrap">
                <div class="step-item" id="step1">
                    <span class="step-icon">🔍</span>
                    <span>Tokenizing & Hashing Code</span>
                </div>
                <div class="step-item" id="step2">
                    <span class="step-icon">📊</span>
                    <span>Comparing with Database</span>
                </div>
                <div class="step-item" id="step3">
                    <span class="step-icon">🤖</span>
                    <span>AI Detection (ZeroGPT)</span>
                </div>
                <div class="step-item" id="step4">
                    <span class="step-icon">💡</span>
                    <span>Code Explanation (Gemini)</span>
                </div>
                <div class="step-item" id="step5">
                    <span class="step-icon">📄</span>
                    <span>Generating Report</span>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
const steps = ['step1','step2','step3','step4','step5'];
const times = [800, 1800, 3500, 5500, 7000];
const progress = [15, 35, 60, 85, 100];

// Activate steps one by one
steps.forEach(function(id, i) {
    setTimeout(function() {
        // Mark previous as done
        if (i > 0) {
            var prev = document.getElementById(steps[i-1]);
            prev.classList.remove('active');
            prev.classList.add('done');
            prev.querySelector('.step-icon').textContent = '✅';
        }
        // Activate current
        var curr = document.getElementById(id);
        curr.classList.add('active');
        curr.innerHTML = '<div class="spinner"></div><span>' + curr.querySelector('span').textContent + '</span>';

        // Update progress bar
        document.getElementById('progressBar').style.width = progress[i] + '%';

    }, times[i]);
});

// Redirect to results after all steps
setTimeout(function() {
    var last = document.getElementById('step5');
    last.classList.remove('active');
    last.classList.add('done');
    document.getElementById('progressBar').style.width = '100%';

    setTimeout(function() {
        window.location.href = 'results.php';
    }, 500);
}, 8000);
</script>
</body>
</html>
