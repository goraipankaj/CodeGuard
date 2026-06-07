<?php
    ob_start();
require_once 'includes/auth.php';
require_once 'includes/mailer.php';

if(isLoggedIn()) {
    header("Location: " . ($_SESSION['role']==='admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit();
}

$login_error = '';
$reg_error   = '';
$reg_success = '';
$otp_error   = '';
$active_tab  = 'login';
$show_otp    = false;
$otp_purpose = '';
$otp_email   = '';

// Google OAuth URL
function getGoogleLoginUrl() {
    $client_id    = '310584947355-7sj944b2pe15ovd39f5i8ro5af9drqvb.apps.googleusercontent.com';
    $redirect_uri = 'https://codeguard.byethost8.com/codeguard/google_callback.php';
    $scope        = 'email profile';
    return 'https://accounts.google.com/o/oauth2/auth?'
         . 'client_id='     . urlencode($client_id)
         . '&redirect_uri=' . urlencode($redirect_uri)
         . '&response_type=code'
         . '&scope='        . urlencode($scope)
         . '&access_type=offline'
         . '&prompt=select_account';
}

// ── LOGIN — Direct (no OTP) ──
if(isset($_POST['action']) && $_POST['action']==='login') {
    $active_tab = 'login';
    $email = trim($_POST['email']);
    $pass  = trim($_POST['password']);
    $stmt  = mysqli_prepare($conn,"SELECT * FROM users WHERE email=?");
    mysqli_stmt_bind_param($stmt,"s",$email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    if($user && $pass == $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];
       if($user['role']==='admin'){
    header("Location: admin/dashboard.php");
} else {
    header("Location: student/dashboard.php");
}
        exit();
    } else {
        $login_error = "Invalid email or password!";
    }
}

// ── REGISTER STEP 1 — Send OTP ──
if(isset($_POST['action']) && $_POST['action']==='register_send_otp') {
    $active_tab = 'register';
    $name  = trim($_POST['reg_name']);
    $email = trim($_POST['reg_email']);
    $pass  = trim($_POST['reg_password']);

    $check = mysqli_prepare($conn,"SELECT id FROM users WHERE email=?");
    mysqli_stmt_bind_param($check,"s",$email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if(mysqli_stmt_num_rows($check) > 0) {
        $reg_error = "Email already registered!";
    } else {
        $otp  = generateOTP();
        saveOTP($conn, $email, $otp, 'register');
        $sent = sendOTP($email, $name, $otp, 'register');
        if($sent) {
            $_SESSION['otp_email']    = $email;
            $_SESSION['otp_purpose']  = 'register';
            $_SESSION['reg_name']     = $name;
            $_SESSION['reg_password'] = password_hash($pass, PASSWORD_DEFAULT);
            $show_otp    = true;
            $otp_purpose = 'register';
            $otp_email   = $email;
        } else {
            $reg_error = "OTP send karne mein error! Gmail settings check karo.";
        }
    }
}

// ── REGISTER STEP 2 — Verify OTP ──
if(isset($_POST['action']) && $_POST['action']==='register_verify_otp') {
    $active_tab  = 'register';
    $show_otp    = true;
    $otp_purpose = 'register';
    $email  = $_SESSION['otp_email']    ?? '';
    $otp    = trim($_POST['otp_code']);

    if(verifyOTP($conn, $email, $otp, 'register')) {
        $name   = $_SESSION['reg_name']     ?? '';
        $hashed = $_SESSION['reg_password'] ?? '';
        $stmt = mysqli_prepare($conn,"INSERT INTO users (name,email,password,role) VALUES (?,?,?,'student')");
        mysqli_stmt_bind_param($stmt,"sss",$name,$email,$hashed);
        if(mysqli_stmt_execute($stmt)) {
            unset($_SESSION['otp_email'],$_SESSION['otp_purpose'],$_SESSION['reg_name'],$_SESSION['reg_password']);
            $reg_success = "Account created! Ab login karo.";
            $active_tab  = 'login';
            $show_otp    = false;
        } else {
            $otp_error = "Registration failed! Try again.";
        }
    } else {
        $otp_error = "Invalid or expired OTP!";
        $otp_email = $email;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>CodeGuard — Login</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
body { padding:0; overflow:hidden; }
.auth-page { min-height:100vh; display:flex; position:relative; overflow:hidden; }

/* LEFT */
.auth-left {
  width:55%; background:var(--bg2);
  display:flex; flex-direction:column;
  justify-content:center; align-items:center;
  padding:60px; position:relative; overflow:hidden;
  border-right:1px solid var(--border);
}
.auth-left::before {
  content:''; position:absolute; inset:0;
  background:
    radial-gradient(ellipse 60% 60% at 30% 40%, rgba(41,121,255,0.12), transparent),
    radial-gradient(ellipse 40% 40% at 80% 80%, rgba(0,229,255,0.07), transparent);
  pointer-events:none;
}
.auth-left-content { position:relative; z-index:1; max-width:480px; width:100%; }
.brand { display:flex; align-items:center; gap:14px; margin-bottom:44px; }
.brand-icon {
  width:52px; height:52px;
  background:linear-gradient(135deg,var(--blue),var(--cyan));
  border-radius:14px; display:flex; align-items:center;
  justify-content:center; font-size:22px;
  box-shadow:0 0 40px rgba(0,229,255,0.3);
  animation:floatIcon 4s ease-in-out infinite;
}
.brand-name { font-family:'JetBrains Mono',monospace; font-size:22px; font-weight:700; color:var(--cyan); letter-spacing:3px; }
.brand-ver  { font-size:10px; color:var(--text3); font-family:'JetBrains Mono',monospace; margin-top:2px; letter-spacing:1px; }
.hero-title { font-size:36px; font-weight:800; line-height:1.2; margin-bottom:14px; letter-spacing:-1px; }
.hero-title span { background:linear-gradient(135deg,var(--cyan),var(--blue)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.hero-sub { font-size:14px; color:var(--text2); line-height:1.7; margin-bottom:36px; }
.feature-list { display:flex; flex-direction:column; gap:12px; }
.feature-item {
  display:flex; align-items:center; gap:14px;
  padding:13px 16px;
  background:rgba(41,121,255,0.05);
  border:1px solid rgba(41,121,255,0.1);
  border-radius:12px; transition:all 0.2s;
}
.feature-item:hover { background:rgba(41,121,255,0.1); transform:translateX(4px); }
.feature-icon { font-size:20px; flex-shrink:0; }
.feature-text strong { display:block; font-size:13px; font-weight:700; color:var(--text); }
.feature-text span   { font-size:12px; color:var(--text3); }
.float-code {
  position:absolute; font-family:'JetBrains Mono',monospace;
  font-size:11px; color:rgba(41,121,255,0.2); pointer-events:none; white-space:nowrap;
}
.float-code.c1 { top:10%; left:5%;    animation:floatCode 8s  ease-in-out infinite; }
.float-code.c2 { top:60%; right:3%;   animation:floatCode 10s ease-in-out infinite reverse; }
.float-code.c3 { bottom:15%; left:10%; animation:floatCode 12s ease-in-out infinite 2s; }
@keyframes floatCode {
  0%,100% { transform:translateY(0) rotate(-2deg); opacity:0.3; }
  50%     { transform:translateY(-14px) rotate(2deg); opacity:0.6; }
}

/* RIGHT */
.auth-right {
  width:45%; display:flex; align-items:center;
  justify-content:center; padding:40px;
  position:relative; background:var(--bg); overflow-y:auto;
}
.auth-right::before {
  content:''; position:absolute; inset:0;
  background:radial-gradient(ellipse 80% 60% at 80% 20%, rgba(0,229,255,0.04), transparent);
  pointer-events:none;
}
.auth-form-wrap { width:100%; max-width:400px; position:relative; z-index:1; padding:10px 0; }
.form-header { margin-bottom:24px; }
.form-header h2 { font-size:26px; font-weight:800; letter-spacing:-0.5px; margin-bottom:5px; }
.form-header p  { font-size:13px; color:var(--text2); }

/* TABS */
.form-tabs {
  display:flex; background:var(--bg2);
  border-radius:12px; padding:4px;
  margin-bottom:22px; border:1px solid var(--border);
}
.form-tab {
  flex:1; padding:10px; border:none; border-radius:9px;
  background:transparent; color:var(--text2);
  font-family:'Plus Jakarta Sans',sans-serif;
  font-size:13px; font-weight:700; cursor:pointer;
  transition:all 0.25s;
  display:flex; align-items:center; justify-content:center; gap:6px;
}
.form-tab.active {
  background:linear-gradient(135deg,var(--blue2),var(--blue));
  color:#fff; box-shadow:0 4px 16px rgba(41,121,255,0.35);
}

/* PANELS */
.form-panel { display:none; }
.form-panel.active { display:block; }

/* INPUTS */
.input-wrap { position:relative; margin-bottom:14px; }
.input-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); font-size:15px; pointer-events:none; }
.input-label { display:block; font-size:11px; font-weight:700; color:var(--text2); margin-bottom:7px; text-transform:uppercase; letter-spacing:1.2px; }
.input-wrap input {
  width:100%; background:var(--bg2); border:1px solid var(--border);
  border-radius:11px; padding:12px 16px 12px 42px;
  color:var(--text); font-family:'JetBrains Mono',monospace;
  font-size:13px; outline:none; transition:all 0.25s;
}
.input-wrap input:focus { border-color:var(--blue); box-shadow:0 0 0 3px rgba(41,121,255,0.15); background:var(--bg3); }
.input-wrap input::placeholder { color:var(--text3); }

/* OTP DIGITS */
.otp-digit {
  width:52px; height:60px;
  background:var(--bg2); border:2px solid var(--border);
  border-radius:12px; text-align:center;
  font-family:'JetBrains Mono',monospace;
  font-size:24px; font-weight:800; color:var(--cyan);
  outline:none; transition:all 0.2s;
}
.otp-digit:focus { border-color:var(--blue); box-shadow:0 0 0 3px rgba(41,121,255,0.2); background:var(--bg3); }

/* BUTTONS */
.btn-login {
  width:100%; padding:13px; margin-top:4px;
  background:linear-gradient(135deg,var(--blue2),var(--blue));
  border:none; border-radius:11px; color:#fff;
  font-family:'Plus Jakarta Sans',sans-serif;
  font-size:14px; font-weight:700; cursor:pointer;
  transition:all 0.25s; box-shadow:0 4px 20px rgba(41,121,255,0.35);
  display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-login:hover { transform:translateY(-2px); box-shadow:0 8px 32px rgba(41,121,255,0.5); }

.btn-register {
  width:100%; padding:13px; margin-top:4px;
  background:linear-gradient(135deg,#00897b,#00bcd4);
  border:none; border-radius:11px; color:#fff;
  font-family:'Plus Jakarta Sans',sans-serif;
  font-size:14px; font-weight:700; cursor:pointer;
  transition:all 0.25s; box-shadow:0 4px 20px rgba(0,188,212,0.3);
  display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-register:hover { transform:translateY(-2px); box-shadow:0 8px 32px rgba(0,188,212,0.45); }

.btn-verify {
  width:100%; padding:13px; margin-top:8px;
  background:linear-gradient(135deg,#6a1b9a,#7c4dff);
  border:none; border-radius:11px; color:#fff;
  font-family:'Plus Jakarta Sans',sans-serif;
  font-size:14px; font-weight:700; cursor:pointer;
  transition:all 0.25s; box-shadow:0 4px 20px rgba(124,77,255,0.35);
  display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-verify:hover { transform:translateY(-2px); box-shadow:0 8px 32px rgba(124,77,255,0.5); }

.btn-back {
  width:100%; padding:10px; margin-top:8px;
  background:transparent; border:1px solid var(--border);
  border-radius:11px; color:var(--text2);
  font-family:'Plus Jakarta Sans',sans-serif;
  font-size:13px; font-weight:600; cursor:pointer; transition:all 0.2s;
}
.btn-back:hover { border-color:var(--blue); color:var(--cyan); }

/* GOOGLE BUTTON */
.btn-google {
  width:100%; padding:13px; margin-top:4px;
  background:#fff; border:1px solid #dadce0;
  border-radius:11px; color:#3c4043;
  font-family:'Plus Jakarta Sans',sans-serif;
  font-size:14px; font-weight:600; cursor:pointer;
  transition:all 0.25s;
  display:flex; align-items:center; justify-content:center; gap:10px;
  text-decoration:none;
}
.btn-google:hover { background:#f8f9fa; box-shadow:0 2px 10px rgba(0,0,0,0.15); transform:translateY(-2px); }

.divider-or {
  display:flex; align-items:center; gap:10px; margin:16px 0;
}
.divider-or span { font-size:12px; color:var(--text3); white-space:nowrap; }
.divider-or::before, .divider-or::after {
  content:''; flex:1; height:1px; background:var(--border);
}

.terms-text { font-size:11px; color:var(--text3); text-align:center; margin-top:14px; line-height:1.6; }

.grid-bg {
  position:fixed; inset:0; z-index:0; pointer-events:none;
  background:
    linear-gradient(rgba(41,121,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(41,121,255,0.02) 1px, transparent 1px);
  background-size:50px 50px;
}
</style>
</head>
<body>
<div class="grid-bg"></div>

<div class="auth-page">

  <!-- LEFT PANEL -->
  <div class="auth-left">
    <div class="float-code c1">for(int i=0; i&lt;n; i++) {<br>&nbsp;&nbsp;if(arr[i]==target) return i;<br>}</div>
    <div class="float-code c2">SELECT * FROM submissions<br>WHERE risk_level='high';</div>
    <div class="float-code c3">final = (token*0.4)+(lcs*0.35)+(hash*0.25);</div>

    <div class="auth-left-content">
      <div class="brand">
        <div class="brand-icon">🛡️</div>
        <div>
          <div class="brand-name">CODEGUARD</div>
          <div class="brand-ver">v2.0 — Plagiarism Detection</div>
        </div>
      </div>
      <div class="hero-title">Detect Code<br><span>Plagiarism</span><br>Instantly</div>
      <div class="hero-sub">Advanced hybrid algorithm using Token Matching, LCS, and Hash Fingerprinting to detect copied code with high accuracy.</div>
      <div class="feature-list">
        <div class="feature-item">
          <span class="feature-icon">🔍</span>
          <div class="feature-text"><strong>3 Algorithm Detection</strong><span>Token Match + LCS + Hash Fingerprint</span></div>
        </div>
        <div class="feature-item">
          <span class="feature-icon">📊</span>
          <div class="feature-text"><strong>Visual Reports</strong><span>Charts, bars, and detailed analysis</span></div>
        </div>
        <div class="feature-item">
          <span class="feature-icon">⚡</span>
          <div class="feature-text"><strong>Instant Results</strong><span>LOW / MEDIUM / HIGH risk classification</span></div>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="auth-right">
    <div class="auth-form-wrap">

      <div class="form-header">
        <h2 id="form-title">
          <?php echo $show_otp ? '🔐 Verify OTP' : 'Welcome Back 👋'; ?>
        </h2>
        <p id="form-sub">
          <?php echo $show_otp ? 'Enter the 6-digit code sent to your email' : 'Sign in to your CodeGuard account'; ?>
        </p>
      </div>

      <!-- TABS — hide on OTP screen -->
      <?php if(!$show_otp): ?>
      <div class="form-tabs">
        <button class="form-tab <?php echo $active_tab==='login'?'active':''; ?>"
                onclick="switchTab('login')">🔐 Login</button>
        <button class="form-tab <?php echo $active_tab==='register'?'active':''; ?>"
                onclick="switchTab('register')">📝 Register</button>
      </div>
      <?php endif; ?>

      <!-- ══ LOGIN PANEL ══ -->
      <div id="panel-login" class="form-panel <?php echo ($active_tab==='login' && !$show_otp)?'active':''; ?>">

        <?php if($login_error): ?>
        <div class="alert alert-danger" style="margin-bottom:14px">❌ <?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>
        <?php if($reg_success): ?>
        <div class="alert alert-success" style="margin-bottom:14px">✅ <?php echo $reg_success; ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="action" value="login">
          <label class="input-label">Email Address</label>
          <div class="input-wrap">
            <span class="input-icon">📧</span>
            <input type="email" name="email" placeholder="your@email.com"
              value="<?php echo isset($_POST['email'])?htmlspecialchars($_POST['email']):''; ?>"
              required autofocus>
          </div>
          <label class="input-label">Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" name="password" placeholder="Enter your password" required>
          </div>
          <button type="submit" class="btn-login">🚀 Sign In to CodeGuard</button>
        </form>

        <!-- OR Divider -->
        <div class="divider-or"><span>OR</span></div>

        <!-- Google Login -->
        <a href="<?php echo getGoogleLoginUrl(); ?>" class="btn-google">
          <svg width="18" height="18" viewBox="0 0 48 48">
            <path fill="#FFC107" d="M43.6 20H24v8h11.3C33.6 33.1 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.8 1.1 7.9 2.9l5.7-5.7C34.1 6.5 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 19.7-8 19.7-20 0-1.3-.1-2.7-.1-4z"/>
            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 15.1 18.9 12 24 12c3 0 5.8 1.1 7.9 2.9l5.7-5.7C34.1 6.5 29.3 4 24 4 16.3 4 9.7 8.4 6.3 14.7z"/>
            <path fill="#4CAF50" d="M24 44c5.2 0 9.9-1.9 13.5-5l-6.2-5.2C29.4 35.5 26.8 36 24 36c-5.2 0-9.6-3-11.3-7.3l-6.5 5C9.6 39.5 16.3 44 24 44z"/>
            <path fill="#1976D2" d="M43.6 20H24v8h11.3c-.9 2.4-2.5 4.4-4.6 5.8l6.2 5.2C40.7 35.5 44 30.2 44 24c0-1.3-.1-2.7-.4-4z"/>
          </svg>
          Continue with Google
        </a>

      </div>

      <!-- ══ REGISTER PANEL ══ -->
      <div id="panel-register" class="form-panel <?php echo ($active_tab==='register' && !$show_otp)?'active':''; ?>">

        <?php if($reg_error): ?>
        <div class="alert alert-danger" style="margin-bottom:14px">❌ <?php echo htmlspecialchars($reg_error); ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="action" value="register_send_otp">
          <label class="input-label">Full Name</label>
          <div class="input-wrap">
            <span class="input-icon">👤</span>
            <input type="text" name="reg_name" placeholder="Enter your full name"
              value="<?php echo isset($_POST['reg_name'])?htmlspecialchars($_POST['reg_name']):''; ?>" required>
          </div>
          <label class="input-label">Email Address</label>
          <div class="input-wrap">
            <span class="input-icon">📧</span>
            <input type="email" name="reg_email" placeholder="your@email.com"
              value="<?php echo isset($_POST['reg_email'])?htmlspecialchars($_POST['reg_email']):''; ?>" required>
          </div>
          <label class="input-label">Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" name="reg_password" placeholder="Create a strong password" required>
          </div>
          <button type="submit" class="btn-register">📧 Send OTP & Register</button>
        </form>

        <!-- OR Divider -->
        <div class="divider-or"><span>OR</span></div>

        <!-- Google Register -->
        <a href="<?php echo getGoogleLoginUrl(); ?>" class="btn-google">
          <svg width="18" height="18" viewBox="0 0 48 48">
            <path fill="#FFC107" d="M43.6 20H24v8h11.3C33.6 33.1 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.8 1.1 7.9 2.9l5.7-5.7C34.1 6.5 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 19.7-8 19.7-20 0-1.3-.1-2.7-.1-4z"/>
            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 15.1 18.9 12 24 12c3 0 5.8 1.1 7.9 2.9l5.7-5.7C34.1 6.5 29.3 4 24 4 16.3 4 9.7 8.4 6.3 14.7z"/>
            <path fill="#4CAF50" d="M24 44c5.2 0 9.9-1.9 13.5-5l-6.2-5.2C29.4 35.5 26.8 36 24 36c-5.2 0-9.6-3-11.3-7.3l-6.5 5C9.6 39.5 16.3 44 24 44z"/>
            <path fill="#1976D2" d="M43.6 20H24v8h11.3c-.9 2.4-2.5 4.4-4.6 5.8l6.2 5.2C40.7 35.5 44 30.2 44 24c0-1.3-.1-2.7-.4-4z"/>
          </svg>
          Sign up with Google
        </a>

        <div class="terms-text">By registering, you agree to use CodeGuard only for academic submission purposes.</div>
      </div>

      <!-- ══ OTP PANEL ══ -->
      <?php if($show_otp && $otp_purpose==='register'): ?>
      <div class="form-panel active">

        <?php if($otp_error): ?>
        <div class="alert alert-danger" style="margin-bottom:14px">❌ <?php echo htmlspecialchars($otp_error); ?></div>
        <?php endif; ?>

        <div style="background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:24px;text-align:center;margin-bottom:16px">
          <div style="font-size:36px;margin-bottom:8px">📬</div>
          <h3 style="font-size:18px;font-weight:800;margin-bottom:6px">Check Your Email!</h3>
          <p style="font-size:13px;color:var(--text2);margin-bottom:10px">OTP bheja gaya hai:</p>
          <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:8px 14px;font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--cyan);display:inline-block">
            <?php echo htmlspecialchars($otp_email); ?>
          </div>
          <div style="font-size:12px;color:var(--text3);margin-top:10px">
            Expires in: <span id="timer" style="color:var(--warning);font-weight:700;font-family:'JetBrains Mono',monospace">05:00</span>
          </div>
        </div>

        <form method="POST">
          <input type="hidden" name="action" value="register_verify_otp">
          <label class="input-label" style="text-align:center;display:block;margin-bottom:12px">Enter 6-Digit OTP</label>
          <div style="display:flex;gap:10px;justify-content:center;margin-bottom:16px">
            <input type="text" class="otp-digit" maxlength="1" id="d1" oninput="moveNext(this,'d2')" onkeydown="moveBack(event,this,null)">
            <input type="text" class="otp-digit" maxlength="1" id="d2" oninput="moveNext(this,'d3')" onkeydown="moveBack(event,this,'d1')">
            <input type="text" class="otp-digit" maxlength="1" id="d3" oninput="moveNext(this,'d4')" onkeydown="moveBack(event,this,'d2')">
            <input type="text" class="otp-digit" maxlength="1" id="d4" oninput="moveNext(this,'d5')" onkeydown="moveBack(event,this,'d3')">
            <input type="text" class="otp-digit" maxlength="1" id="d5" oninput="moveNext(this,'d6')" onkeydown="moveBack(event,this,'d4')">
            <input type="text" class="otp-digit" maxlength="1" id="d6" oninput="collectOTP()"       onkeydown="moveBack(event,this,'d5')">
          </div>
          <input type="hidden" name="otp_code" id="otp_code">
          <button type="submit" class="btn-verify" onclick="return collectOTP()">✅ Verify & Create Account</button>
        </form>

        <button class="btn-back" onclick="window.location.href='login.php'">← Back to Register</button>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script>
function switchTab(tab) {
  document.querySelectorAll('.form-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
  event.target.classList.add('active');
  document.getElementById('panel-' + tab).classList.add('active');
  document.getElementById('form-title').textContent = tab==='login' ? 'Welcome Back 👋' : 'Create Account ✨';
  document.getElementById('form-sub').textContent   = tab==='login' ? 'Sign in to your CodeGuard account' : 'Register to start using CodeGuard';
}

function moveNext(el, nextId) {
  if(el.value.length===1) { const n=document.getElementById(nextId); if(n) n.focus(); }
}
function moveBack(e, el, prevId) {
  if(e.key==='Backspace' && el.value==='' && prevId) document.getElementById(prevId).focus();
}
function collectOTP() {
  const otp = ['d1','d2','d3','d4','d5','d6'].map(id=>document.getElementById(id).value).join('');
  document.getElementById('otp_code').value = otp;
  return otp.length===6;
}

const timerEl = document.getElementById('timer');
if(timerEl) {
  let seconds = 300;
  const interval = setInterval(() => {
    seconds--;
    const m = String(Math.floor(seconds/60)).padStart(2,'0');
    const s = String(seconds%60).padStart(2,'0');
    timerEl.textContent = m+':'+s;
    if(seconds<=0) {
      clearInterval(interval);
      timerEl.textContent='EXPIRED';
      timerEl.style.color='var(--danger)';
    }
  }, 1000);
}

<?php if($active_tab==='register' && !$show_otp): ?>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.form-tab')[1].click();
});
<?php endif; ?>
</script>
</body>
</html>
