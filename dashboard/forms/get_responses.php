<?php
session_start();
require_once '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    die('<header><link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css"></header><style>body {min-height:60vh !important;}</style><h1 style="text-align:center;">دسترسی غیرمجاز.</h1>');
}

$form_id = (int)($_GET['form_id'] ?? 0);
if ($form_id <= 0) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'شناسه فرم نامعتبر'], JSON_UNESCAPED_UNICODE));
}


$stmt = $pdo->prepare("SELECT content FROM forms WHERE id = ? AND created_by = ?");
$stmt->execute([$form_id, $_SESSION['user_id']]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$form) {
    header('Content-Type: application/json');
    die('<header><link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css"></header><style>body {min-height:60vh !important;}</style><h1 style="text-align:center;">فرم یافت نشد</h1>');
}

$form_content = json_decode($form['content'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    header('Content-Type: application/json');
    die('<header><link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css"></header><style>body {min-height:60vh !important;}</style><h1 style="text-align:center;">خطا در تجزیه محتوای فرم</h1>');
}
$questions = $form_content['questions'] ?? [];


$stmt = $pdo->prepare("SELECT response FROM form_responses WHERE form_id = ?");
$stmt->execute([$form_id]);
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = ['questions' => []];
foreach ($questions as $index => $question) {
    if (!in_array($question['type'], ['multiple', 'checkbox', 'dropdown'])) {
        continue; 
    }
    $options = array_map('strval', $question['options'] ?? []);
    $counts = array_fill_keys($options, 0);
    foreach ($responses as $response) {
        $responses = json_decode($response['response'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue; 
        }
        $response = $responses[$index] ?? null;
        if ($question['type'] === 'checkbox' && is_array($response)) {
            foreach ($response as $val) {
                if (isset($counts[$val])) {
                    $counts[$val]++;
                }
            }
        } elseif (isset($counts[$response])) {
            $counts[$response]++;
        }
    }
    $result['questions'][$index] = [
        'label' => htmlspecialchars($question['label'] ?? 'سوال ' . ($index + 1), ENT_QUOTES, 'UTF-8'),
        'options' => array_map(function($opt) { return htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); }, array_keys($counts)),
        'counts' => array_values($counts)
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>