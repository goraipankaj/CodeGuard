<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: /codeguard/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Analyzing... - CodeGuard</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
        background: #0a0f1e;
        color: white;
        font-family: Arial, sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
    }
    .wrap {
        text-align: center;
        padding: 40px 20px;
    }
    .shield {
        font-size: 80px;
        animation: pulse 1.5s infinite;
        display: block;
        margin-bottom: 20px;
    }
    @keyframes pulse {
        0%   { transform: scale(1);   opacity: 1; }
        50%  { transform: scale(1.15); opacity: 0.7; }
        100% { transform: scale(1);   opacity: 1; }
    }
    h2 {
        font-size: 26px;
        color: #00e5ff;
        margin-bottom: 8px;
    }
    p {
        color: #7a9cc0;
        font-size: 14px;
        margin-bottom: 35px;
    }
    .progress-outer {
        width: 380px;
        max-width: 90vw;
        height: 8px;
        background: rgba(255,255,255,0.08);
        border-radius: 10px;
        margin: 0 auto 30px;
        overflow: hidden;
    }
    .progress-inner {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #2979ff, #00e5ff);
        border-radius: 10px;
        transition: width 0.6s ease;
    }
    .steps {
        width: 380px;
        max-width: 90vw;
        margin: 0 auto;
    }
    .step {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        border-radius: 10px;
        margin-bottom: 10px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        color: #444;
        font-size: 14px;
        transition: all 0.4s ease;
        text-align: left;
    }
    .step.active {
        background: rgba(0,229,255,0.08);
        border-color: rgba(0,229,255,0.25);
        color: #00e5ff;
    }
    .step.done {
        background: rgba(0,200,83,0.08);
        border-color: rgba(0,200,83,0.2);
        color: #00c853;
    }
    .spinner {
        width: 18px;
        height: 18px;
        border: 2px solid rgba(0,229,255,0.2);
        border-top-color: #00e5ff;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        flex-shrink: 0;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .icon { font-size: 18px; flex-shrink: 0; }
</style>
</head>
<body>

<div class="wrap">
    <span class="shield">🛡️</span>
    <h2>Analyzing Your Code...</h2>
    <p>Please wait — CodeGuard is checking your submission</p>

    <div class="progress-outer">
        <div class="progress-inner" id="bar"></div>
    </div>

    <div class="steps">
        <div class="step" id="s1"><span class="icon">🔍</span> Tokenizing & Hashing Code</div>
        <div class="step" id="s2"><span class="icon">📊</span> Comparing with Database</div>
        <div class="step" id="s3"><span class="icon">🤖</span> AI Detection (ZeroGPT)</div>
        <div class="step" id="s4"><span class="icon">💡</span> Code Explanation (Gemini)</div>
        <div class="step" id="s5"><span class="icon">📄</span> Generating Report</div>
    </div>
</div>

<script>
var steps   = ['s1','s2','s3','s4','s5'];
var times   = [600, 1800, 3200, 5000, 6800];
var progArr = [20, 40, 60, 80, 100];

steps.forEach(function(id, i) {
    setTimeout(function() {

        // Previous step done
        if(i > 0) {
            var prev = document.getElementById(steps[i-1]);
            prev.className = 'step done';
            prev.innerHTML = '<span class="icon">✅</span> ' + prev.innerText;
        }

        // Current step active
        var curr = document.getElementById(id);
        curr.className = 'step active';
        curr.innerHTML = '<div class="spinner"></div> ' + curr.innerText;

        // Progress bar
        document.getElementById('bar').style.width = progArr[i] + '%';

    }, times[i]);
});

// Last step done + redirect
setTimeout(function() {
    var last = document.getElementById('s5');
    last.className = 'step done';
    last.innerHTML = '<span class="icon">✅</span> Report Ready!';
    document.getElementById('bar').style.width = '100%';

    setTimeout(function() {
        window.location.href = 'results.php';
    }, 600);
}, 8200);
</script>

</body>
</html>
