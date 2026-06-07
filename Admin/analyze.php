<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/zerogpt.php';
require_once 'includes/gemini.php';

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    if(!empty($_FILES['code_file']['tmp_name'])){
        $code     = file_get_contents($_FILES['code_file']['tmp_name']);
        $filename = $_FILES['code_file']['name'];
    } elseif(!empty($_POST['code_text'])){
        $code     = $_POST['code_text'];
        $filename = "Pasted code.php";
    } else {
        die("No code provided!");
    }

    $totalLines = count(explode("\n", $code));
    if($totalLines == 0) $totalLines = 1;

    $gemini  = analyzeCode($code);
    $zeroGPT = detectAI($code);
    $aiScore = ($zeroGPT['score'] > 0) ? $zeroGPT['score'] : $gemini['ai_score'];

    $plagiarismScore = $gemini['plagiarism_score'];
    $selfScore       = $gemini['self_score'];

    $filename_safe    = mysqli_real_escape_string($conn, $filename);
    $explanation_safe = mysqli_real_escape_string($conn, $gemini['explanation']);

    $sql = "INSERT INTO analysis_reports
    (user_id, filename, total_lines, self_percent, common_percent,
     copied_percent, fingerprints, similarity_percent, ai_score, ai_explanation)
    VALUES
    ('$user_id','$filename_safe','$totalLines','$selfScore','0',
     '$plagiarismScore','','$plagiarismScore','$aiScore','$explanation_safe')";

    $result = mysqli_query($conn, $sql);
    if(!$result) die("DB Error: " . mysqli_error($conn));

    $_SESSION['result'] = array(
        'filename'     => $filename,
        'total'        => $totalLines,
        'self'         => $selfScore,
        'plagiarism'   => $plagiarismScore,
        'ai_score'     => round($aiScore, 2),
        'ai_explain'   => $gemini['explanation'],
        'what_it_does' => $gemini['what_it_does'],
        'is_common'    => $gemini['is_common'],
        'suspicious'   => $gemini['suspicious'],
        'risk'         => $gemini['risk']
    );

    header("Location: student/results.php");
    exit();
}
?>
