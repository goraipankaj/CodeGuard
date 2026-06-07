<?php
function analyzeCode($code) {
    $key = defined('GROQ_KEY') ? GROQ_KEY : '';
    if ($key == '') {
        return array(
            'plagiarism_score' => 0,
            'ai_score'         => 0,
            'self_score'       => 100,
            'risk'             => 'UNKNOWN',
            'explanation'      => 'API key not set',
            'what_it_does'     => 'N/A',
            'is_common'        => 'N/A',
            'suspicious'       => 'N/A',
            'error'            => true
        );
    }

    $url = 'https://api.groq.com/openai/v1/chat/completions';

    $prompt = 'You are a plagiarism detection AI. Analyze this student code and respond ONLY in this exact format with no extra text:

PLAGIARISM_SCORE: [0-100]
AI_GENERATED_SCORE: [0-100]
SELF_WRITTEN_SCORE: [0-100]
RISK_LEVEL: [LOW or MEDIUM or HIGH]
WHAT_IT_DOES: [1 line]
IS_COMMON_ALGORITHM: [Yes or No - explain in 5 words]
SUSPICIOUS_PATTERNS: [Yes or No - explain in 5 words]
EXPLANATION: [2-3 lines about originality]

Code:
' . substr($code, 0, 2000);

    $data = json_encode(array(
        'model'    => 'llama-3.3-70b-versatile',
        'messages' => array(
            array('role' => 'user', 'content' => $prompt)
        ),
        'max_tokens'  => 500,
        'temperature' => 0.1
    ));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $key
    ));

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if($error){
        return array(
            'plagiarism_score' => 0,
            'ai_score'         => 0,
            'self_score'       => 100,
            'risk'             => 'UNKNOWN',
            'explanation'      => 'cURL Error: ' . $error,
            'what_it_does'     => 'N/A',
            'is_common'        => 'N/A',
            'suspicious'       => 'N/A',
            'error'            => true
        );
    }

    $result = json_decode($response, true);

    if(!isset($result['choices'][0]['message']['content'])){
        return array(
            'plagiarism_score' => 0,
            'ai_score'         => 0,
            'self_score'       => 100,
            'risk'             => 'UNKNOWN',
            'explanation'      => 'No response: ' . json_encode($result),
            'what_it_does'     => 'N/A',
            'is_common'        => 'N/A',
            'suspicious'       => 'N/A',
            'error'            => true
        );
    }

    $text = $result['choices'][0]['message']['content'];

    function extractVal($text, $key){
        preg_match('/' . $key . ':\s*(.+)/i', $text, $m);
        return isset($m[1]) ? trim($m[1]) : 'N/A';
    }

    $plagiarism  = intval(extractVal($text, 'PLAGIARISM_SCORE'));
    $aiScore     = intval(extractVal($text, 'AI_GENERATED_SCORE'));
    $selfScore   = intval(extractVal($text, 'SELF_WRITTEN_SCORE'));
    $risk        = extractVal($text, 'RISK_LEVEL');
    $whatItDoes  = extractVal($text, 'WHAT_IT_DOES');
    $isCommon    = extractVal($text, 'IS_COMMON_ALGORITHM');
    $suspicious  = extractVal($text, 'SUSPICIOUS_PATTERNS');
    $explanation = extractVal($text, 'EXPLANATION');

    return array(
        'plagiarism_score' => $plagiarism,
        'ai_score'         => $aiScore,
        'self_score'       => $selfScore,
        'risk'             => $risk,
        'explanation'      => $explanation,
        'what_it_does'     => $whatItDoes,
        'is_common'        => $isCommon,
        'suspicious'       => $suspicious,
        'error'            => false
    );
}

function explainCode($code){
    $r = analyzeCode($code);
    return array('text' => $r['explanation'], 'error' => $r['error']);
}
?>
