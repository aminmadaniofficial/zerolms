<?php
/**
 *     _____                    __   __  ___ _____
 *    /__  /  ___  _________   / /  /  |/  // ___/
 *      / /  / _ \/ ___/ __ \ / /  / /|_/ / \__ \ 
 *     / /__/  __/ /  / /_/ // /__/ /  / / ___/ / 
 *    /____/\___/_/   \____//____/_/  /_/ /____/  
 * 
 * ------------------------------------------------------------
 *  System      : Zero LMS Core Engine
 *  Author      : Amin Madani
 *  Created     : 2026
 *  Notice      : Unauthorized copying or modification of this file,
 *                via any medium is strictly prohibited.
 * ------------------------------------------------------------
 */

session_start();
require_once '../../db.php'; 

// Verify active user session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit;
}

$user_id = $_SESSION['user_id'];
$api_key = getenv('OPENROUTER_API_KEY') ?: ''; 

/**
 * Create a new AI chat thread session in database.
 * 
 * @param PDO $pdo Active PDO handle
 * @param int $user_id Owner user ID
 * @param string $title Chat title summary
 * @return int|null Inserted chat ID or null on failure
 */
function createChat($pdo, $user_id, $title)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO aichats (user_id, title) VALUES (?, ?)");
        $stmt->execute([$user_id, $title]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("DB Create Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Fetch existing chat session or create a new one.
 * 
 * @param PDO $pdo
 * @param int $user_id
 * @param string $message
 * @param int|null $chat_id
 * @return int
 */
function getOrCreateChat($pdo, $user_id, $message, $chat_id = null)
{
    if ($chat_id) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM aichats WHERE id = ? AND user_id = ?");
            $stmt->execute([$chat_id, $user_id]);
            if ($stmt->fetch())
                return (int) $chat_id;
        } catch (PDOException $e) {
            error_log("DB Get Error: " . $e->getMessage());
        }
    }
    $title = mb_substr($message, 0, 30) . '...';
    return createChat($pdo, $user_id, $title);
}

/**
 * Save user or bot message entry to history database table.
 * 
 * @param PDO $pdo
 * @param int $chat_id
 * @param string $sender 'user' or 'bot'
 * @param string $content Message body
 */
function saveMessage($pdo, $chat_id, $sender, $content)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO aimessages (chat_id, sender, content) VALUES (?, ?, ?)");
        $stmt->execute([$chat_id, $sender, $content]);
    } catch (PDOException $e) {
        error_log("DB Save Error: " . $e->getMessage());
    }
}

/**
 * Load paired user and assistant message history for specified chat with ownership verification.
 * 
 * @param PDO $pdo
 * @param int $chat_id
 * @param int|null $user_id
 * @return array Array of user text and bot response pairs
 */
function loadChatHistory($pdo, $chat_id, $user_id = null)
{
    try {
        if ($user_id !== null) {
            $check = $pdo->prepare("SELECT id FROM aichats WHERE id = ? AND user_id = ?");
            $check->execute([$chat_id, $user_id]);
            if (!$check->fetch()) {
                return [];
            }
        }
        $stmt = $pdo->prepare("SELECT sender, content FROM aimessages WHERE chat_id = ? ORDER BY created_at ASC");
        $stmt->execute([$chat_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $history = [];
        $paired = [];
        foreach ($rows as $row) {
            if ($row['sender'] === 'user') {
                $paired['text'] = $row['content'];
            } elseif ($row['sender'] === 'bot' && isset($paired['text'])) {
                $paired['response'] = $row['content'];
                $history[] = $paired;
                $paired = [];
            }
        }
        return $history;
    } catch (PDOException $e) {
        error_log("DB Load Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch all chat threads belonging to user.
 * 
 * @param PDO $pdo
 * @param int $user_id
 * @return array
 */
function getUserChats($pdo, $user_id)
{
    try {
        $stmt = $pdo->prepare("SELECT id, title, created_at FROM aichats WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("DB Chats Error: " . $e->getMessage());
        return [];
    }
}

// API Endpoint: Get user chat threads
if (isset($_GET['action']) && $_GET['action'] === 'get_aichats') {
    $chats = getUserChats($GLOBALS['pdo'], $user_id);
    header('Content-Type: application/json');
    echo json_encode($chats);
    exit;
}

// API Endpoint: Create new chat thread
if (isset($_GET['action']) && $_GET['action'] === 'create_chat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'] ?? '';
    if (empty($message)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'پیام خالی']);
        exit;
    }
    $title = mb_substr($message, 0, 30) . '...';
    $chat_id = createChat($GLOBALS['pdo'], $user_id, $title);
    header('Content-Type: application/json');
    echo json_encode(['chat_id' => $chat_id ?? 0]);
    exit;
}

// API Endpoint: Save message entry
if (isset($_GET['action']) && $_GET['action'] === 'save_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $chat_id = $_POST['chat_id'] ?? '';
    $message = $_POST['message'] ?? '';
    $response = $_POST['response'] ?? '';
    if ($chat_id && !empty($message) && !empty($response)) {
        // Verify chat belongs to current user
        $check = $GLOBALS['pdo']->prepare("SELECT id FROM aichats WHERE id = ? AND user_id = ?");
        $check->execute([$chat_id, $user_id]);
        if ($check->fetch()) {
            saveMessage($GLOBALS['pdo'], $chat_id, 'user', $message);
            saveMessage($GLOBALS['pdo'], $chat_id, 'bot', $response);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'دسترسی غیرمجاز']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'داده ناقص']);
    }
    exit;
}

// API Endpoint: Update chat thread title
if (isset($_GET['action']) && $_GET['action'] === 'update_chat_title' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $chat_id = $_POST['chat_id'] ?? '';
    $new_title = $_POST['title'] ?? '';
    if ($chat_id && !empty($new_title)) {
        try {
            $stmt = $GLOBALS['pdo']->prepare("UPDATE aichats SET title = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$new_title, $chat_id, $user_id]);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log("DB Update Title Error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'خطا در بروزرسانی']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'داده ناقص']);
    }
    exit;
}

// API Endpoint: Delete chat thread
if (isset($_GET['action']) && $_GET['action'] === 'delete_chat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $chat_id = $_POST['chat_id'] ?? '';
    if ($chat_id) {
        try {
            $stmt = $GLOBALS['pdo']->prepare("DELETE FROM aichats WHERE id = ? AND user_id = ?");
            $stmt->execute([$chat_id, $user_id]);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log("DB Delete Error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'خطا در حذف']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'داده ناقص']);
    }
    exit;
}

// Handle AJAX chat history loading
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$chat_id = $_GET['chat_id'] ?? null;
if ($chat_id && !isset($_GET['action']) && $is_ajax) {
    $history = loadChatHistory($GLOBALS['pdo'], $chat_id, $user_id);
    header('Content-Type: application/json');
    echo json_encode(['chat_id' => $chat_id, 'history' => $history]);
    exit;
}

// Server-Sent Events (SSE) AI completions streaming endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    $message = $_POST['message'] ?? '';
    $history_json = $_POST['history'] ?? '[]';
    $chat_id = $_POST['chat_id'] ?? null;

    if (empty($message)) {
        echo "data: " . json_encode(['error' => 'پیام خالی است.']) . "\n\n";
        flush();
        exit;
    }

    if (empty($api_key)) {
        echo "data: " . json_encode(['choices' => [['delta' => ['content' => 'کلید هوش مصنوعی (OPENROUTER_API_KEY) در سرور تنظیم نشده است. لطفاً متغیر محیطی مربوطه را پیکربندی نمایید.']]]]) . "\n\n";
        echo "data: [DONE]\n\n";
        flush();
        exit;
    }

    $current_chat_id = getOrCreateChat($GLOBALS['pdo'], $user_id, $message, $chat_id);
    $history = json_decode($history_json, true);

    $system_prompt = "شما «زیرو» (Zero) هستید؛ دستیار هوشمند و تخصصی یادگیری سامانه مدرسه. "
        . "توسعه‌دهنده شما «محمدامین مدنی محمدی» است. "
        . "وظایف شما: "
        . "۱. ارائه پاسخ‌های کاملاً دقیق، کاربردی و منظم با ساختار مارک‌داون. "
        . "۲. حفظ لحن صمیمی، محترمانه و انگیزشی در پاسخ به دانش‌آموزان و معلمان. "
        . "۳. تشویق کاربران به یادگیری مستقل و عدم ارائه پاسخ مستقیم در کارهای آزمونی یا تقلب. "
        . "۴. خلاصه، سریع و بدون زیاده‌گویی پاسخ دهید.";

    $messages = [
        ['role' => 'system', 'content' => $system_prompt]
    ];

    $recent_history = array_slice($history, -8);
    foreach ($recent_history as $msg) {
        $messages[] = ['role' => 'user', 'content' => $msg['text']];
        $messages[] = ['role' => 'assistant', 'content' => $msg['response']];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    $data = json_encode([
        'model' => 'nvidia/nemotron-3-ultra-550b-a55b:free',
        'messages' => $messages,
        'stream' => true
    ]);

    // Stream cURL request to OpenRouter API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://openrouter.ai/api/v1/chat/completions");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json",
    ]);
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data_chunk) {
        echo $data_chunk;
        @ob_flush();
        flush();
        return strlen($data_chunk);
    });

    $exec_result = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("Curl Error: " . $curl_error);
    }

    echo "data: [DONE]\n\n";
    flush();
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>دستیار هوشمند زیرو | Zero AI</title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <style>
        :root {
            --bg-main: #0f172a;
            --bg-sidebar: #1e293b;
            --bg-card: rgba(30, 41, 59, 0.85);
            --accent-blue: #3b82f6;
            --accent-hover: #2563eb;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.1);
        }

        * {
            font-family: 'Vazirmatn', sans-serif;
            box-sizing: border-box;
        }

        body {
            display: flex;
            height: 100vh;
            background-color: var(--bg-main);
            color: var(--text-primary);
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

        /* Mobile Sidebar Backdrop Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(4px);
            z-index: 1040;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active { display: block; opacity: 1; }

        /* Sidebar Styling */
        .sidebar {
            width: 290px;
            background: var(--bg-sidebar);
            height: 100vh;
            border-left: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 16px;
            z-index: 1050;
            transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-new-chat {
            background: linear-gradient(135deg, var(--accent-blue), #1d4ed8);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }

        .chat-list {
            list-style: none;
            padding: 0;
            margin: 15px 0 0 0;
            overflow-y: auto;
            flex: 1;
        }

        .chat-item {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .chat-item:hover, .chat-item.active {
            background: rgba(59, 130, 246, 0.15);
            border-color: var(--accent-blue);
        }

        .chat-title {
            flex-grow: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.88rem;
        }

        .chat-actions { display: flex; gap: 4px; }
        .btn-icon { background: transparent; border: none; color: var(--text-secondary); padding: 4px 6px; font-size: 0.8rem; }
        .btn-icon:hover { color: #f8fafc; }

        /* Main Chat Canvas Area */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            background: var(--bg-main);
            position: relative;
            width: 100%;
        }

        .chat-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 100;
        }

        .chat-box {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Hero Welcome Cards */
        .hero-section {
            margin: auto;
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .hero-icon {
            font-size: 2.8rem;
            color: var(--accent-blue);
            margin-bottom: 12px;
        }

        .prompt-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 20px;
        }

        .prompt-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 14px;
            border-radius: 12px;
            text-align: right;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .prompt-card:hover {
            border-color: var(--accent-blue);
            color: var(--text-primary);
        }

        /* Chat Message Bubbles */
        .chat-message {
            max-width: 82%;
            padding: 12px 16px;
            border-radius: 14px;
            font-size: 0.92rem;
            line-height: 1.6;
            word-wrap: break-word;
        }

        .user-message {
            align-self: flex-start;
            background: linear-gradient(135deg, var(--accent-blue), #1d4ed8);
            color: #ffffff;
            border-bottom-right-radius: 2px;
        }

        .bot-message {
            align-self: flex-end;
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-bottom-left-radius: 2px;
        }

        /* Chat Input Area */
        .input-container {
            padding: 14px 20px;
            background: rgba(15, 23, 42, 0.95);
            border-top: 1px solid var(--border-color);
        }

        .input-group-custom {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 4px 10px;
            display: flex;
            align-items: center;
        }

        .input-group-custom input {
            background: transparent;
            border: none;
            color: var(--text-primary);
            padding: 10px;
            flex: 1;
            outline: none;
            font-size: 0.92rem;
        }

        .btn-send {
            background: var(--accent-blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            width: 38px; height: 38px;
            display: flex; align-items: center; justify-content: center;
        }

        /* Responsive Mobile Toggles */
        #toggleSidebarBtn {
            display: none;
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 1.2rem;
            padding: 4px 8px;
        }

        /* Modal Overlays */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(6px);
            display: flex; align-items: center; justify-content: center;
            z-index: 2000; opacity: 0; pointer-events: none; transition: opacity 0.25s;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-card {
            background: var(--bg-sidebar); border: 1px solid var(--border-color);
            border-radius: 16px; width: 90%; max-width: 400px; padding: 20px;
        }

        footer {
            text-align: center; padding: 8px; font-size: 0.75rem; color: var(--text-secondary);
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                top: 0; right: -300px;
                width: 280px; height: 100vh;
                box-shadow: -10px 0 30px rgba(0,0,0,0.8);
            }
            .sidebar.show { right: 0; }
            #toggleSidebarBtn { display: inline-block; }
            .prompt-cards { grid-template-columns: 1fr; }
            .chat-message { max-width: 92%; }
            .chat-box { padding: 14px; }
            .input-container { padding: 10px 14px; }
        }
    </style>
</head>

<body>
    <!-- Mobile Navigation Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- AI Chat Threads Sidebar -->
    <div class="sidebar" id="sidebar">
        <button id="newChatBtn" class="btn-new-chat">
            <i class="fas fa-plus"></i> گفتگوی جدید
        </button>
        <div class="d-flex align-items-center justify-content-between mt-4 mb-2 px-1">
            <span class="text-secondary" style="font-size: 0.8rem; font-weight: 600;">تاریخچه گفتگوها</span>
        </div>
        <ul id="chatList" class="chat-list"></ul>
    </div>

    <!-- Main Chat Workspace -->
    <div class="main-content">
        <div class="chat-header">
            <div class="d-flex align-items-center gap-2">
                <button id="toggleSidebarBtn"><i class="fas fa-bars"></i></button>
                <i class="fas fa-robot text-primary fs-5"></i>
                <h6 class="m-0 font-weight-bold" style="font-size: 0.95rem;">دستیار هوشمند زیرو (Zero AI)</h6>
            </div>
            <span class="badge bg-success bg-opacity-20 rounded-pill px-3 py-1" style="font-size: 0.72rem;">فعال</span>
        </div>

        <div id="chatBox" class="chat-box"></div>

        <div class="input-container">
            <form id="chatForm">
                <input type="hidden" id="history" name="history" value="[]">
                <input type="hidden" id="chatIdInput" name="chat_id" value="">
                <div class="input-group-custom">
                    <input type="text" id="messageInput" placeholder="سوال خود را بنویسید..." autocomplete="off">
                    <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i></button>
                </div>
            </form>
        </div>

        <footer>
            طراحی شده توسط <a href="https://aminmadani.ir" target="_blank" class="text-primary text-decoration-none">محمدامین مدنی محمدی</a>
        </footer>
    </div>

    <!-- Rename Thread Modal -->
    <div id="renameModal" class="modal-overlay">
        <div class="modal-card">
            <h6 class="mb-3"><i class="fas fa-edit text-primary me-2"></i> تغییر عنوان گفتگو</h6>
            <input type="text" id="renameInput" class="form-control bg-dark text-light border-secondary mb-3" placeholder="عنوان جدید...">
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="closeModals()">انصراف</button>
                <button type="button" id="confirmRenameBtn" class="btn btn-sm btn-primary">ذخیره</button>
            </div>
        </div>
    </div>

    <!-- Delete Thread Modal -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-card">
            <h6 class="mb-2 text-danger"><i class="fas fa-exclamation-triangle me-2"></i> حذف گفتگو</h6>
            <p class="text-secondary mb-4" style="font-size: 0.85rem;">آیا از حذف این گفتگو اطمینان دارید؟</p>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="closeModals()">انصراف</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-sm btn-danger">حذف شود</button>
            </div>
        </div>
    </div>

    <!-- Client-side Logic Script for SSE Chat Interaction -->
    <script>
        const chatBox = document.getElementById('chatBox');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const historyInput = document.getElementById('history');
        const chatIdInput = document.getElementById('chatIdInput');
        const chatList = document.getElementById('chatList');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const toggleSidebarBtn = document.getElementById('toggleSidebarBtn');
        
        let currentChatId = null;
        let history = [];
        let targetChatIdForAction = null;

        // Toggle mobile drawer visibility
        function toggleSidebar() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('active');
        }

        toggleSidebarBtn.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);

        // Format Markdown strings to HTML output
        function formatMessage(text) {
            text = text.replace(/^### (.*$)/gm, '<h6 class="text-primary mt-2">$1</h6>');
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/\n/g, '<br>');
            return text;
        }

        // Render zero-state hero card
        function renderHeroState() {
            chatBox.innerHTML = `
                <div class="hero-section">
                    <div class="hero-icon"><i class="fas fa-brain"></i></div>
                    <h5>سلام! من زیرو هستم؛ دستیار یادگیری شما</h5>
                    <p class="text-secondary style-sm" style="font-size:0.85rem;">چگونه می‌توانم در یادگیری دروس به شما کمک کنم؟</p>
                    <div class="prompt-cards">
                        <div class="prompt-card" onclick="sendQuickPrompt('چگونه برای امتحانات برنامه ریزی درسی داشته باشم؟')">
                            <i class="fas fa-calendar-alt text-primary mb-1 d-block"></i>
                            <strong>برنامه‌ریزی درسی</strong>
                        </div>
                        <div class="prompt-card" onclick="sendQuickPrompt('روش‌های خلاصه‌نویسی صحیح مطالب درسی چیست؟')">
                            <i class="fas fa-pen-fancy text-success mb-1 d-block"></i>
                            <strong>خلاصه‌نویسی مطالب</strong>
                        </div>
                        <div class="prompt-card" onclick="sendQuickPrompt('چند تکنیک برای تمرکز بیشتر هنگام مطالعه بگویید.')">
                            <i class="fas fa-lightbulb text-warning mb-1 d-block"></i>
                            <strong>تکنیک‌های تمرکز</strong>
                        </div>
                        <div class="prompt-card" onclick="sendQuickPrompt('یک تعریف ساده و خلاصه از هوش مصنوعی ارائه بده.')">
                            <i class="fas fa-robot text-info mb-1 d-block"></i>
                            <strong>تعریف هوش مصنوعی</strong>
                        </div>
                    </div>
                </div>
            `;
        }

        function sendQuickPrompt(promptText) {
            messageInput.value = promptText;
            chatForm.dispatchEvent(new Event('submit'));
        }

        // Fetch AI chat threads
        async function loadaichats() {
            try {
                const response = await fetch('?action=get_aichats');
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const aichats = await response.json();
                chatList.innerHTML = '';

                aichats.forEach(chat => {
                    const li = document.createElement('li');
                    li.className = 'chat-item';
                    if (currentChatId == chat.id) li.classList.add('active');

                    const titleSpan = document.createElement('span');
                    titleSpan.className = 'chat-title';
                    titleSpan.textContent = chat.title;

                    const actionsDiv = document.createElement('div');
                    actionsDiv.className = 'chat-actions';

                    const editBtn = document.createElement('button');
                    editBtn.className = 'btn-icon';
                    editBtn.innerHTML = '<i class="fas fa-pen"></i>';
                    editBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        openRenameModal(chat.id, chat.title);
                    });

                    const deleteBtn = document.createElement('button');
                    deleteBtn.className = 'btn-icon delete';
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    deleteBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        openDeleteModal(chat.id);
                    });

                    actionsDiv.appendChild(editBtn);
                    actionsDiv.appendChild(deleteBtn);
                    li.appendChild(titleSpan);
                    li.appendChild(actionsDiv);

                    li.addEventListener('click', () => {
                        loadChat(chat.id);
                        if (window.innerWidth < 768) toggleSidebar();
                    });
                    chatList.appendChild(li);
                });
            } catch (error) {
                console.error('خطا در بارگذاری گفتگوها:', error);
            }
        }

        function openRenameModal(chatId, currentTitle) {
            targetChatIdForAction = chatId;
            document.getElementById('renameInput').value = currentTitle;
            document.getElementById('renameModal').classList.add('active');
        }

        function openDeleteModal(chatId) {
            targetChatIdForAction = chatId;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeModals() {
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
            targetChatIdForAction = null;
        }

        document.getElementById('confirmRenameBtn').addEventListener('click', async () => {
            const newTitle = document.getElementById('renameInput').value.trim();
            if (!newTitle || !targetChatIdForAction) return;

            const formData = new URLSearchParams({ chat_id: targetChatIdForAction, title: newTitle });
            try {
                const response = await fetch('?action=update_chat_title', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    loadaichats();
                    closeModals();
                }
            } catch (error) { console.error('Rename Error:', error); }
        });

        document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
            if (!targetChatIdForAction) return;

            const formData = new URLSearchParams({ chat_id: targetChatIdForAction });
            try {
                const response = await fetch('?action=delete_chat', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    if (currentChatId == targetChatIdForAction) {
                        document.getElementById('newChatBtn').click();
                    } else {
                        loadaichats();
                    }
                    closeModals();
                }
            } catch (error) { console.error('Delete Error:', error); }
        });

        async function loadChat(chatId) {
            currentChatId = chatId;
            chatIdInput.value = chatId;
            history = [];
            chatBox.innerHTML = '';

            try {
                const response = await fetch(`?chat_id=${chatId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const data = await response.json();
                history = data.history || [];

                if (history.length === 0) {
                    renderHeroState();
                } else {
                    history.forEach(msg => {
                        addMessage('شما', msg.text, 'user-message');
                        addMessage('زیرو', msg.response, 'bot-message');
                    });
                }
                chatBox.scrollTop = chatBox.scrollHeight;
            } catch (error) {
                addMessage('سیستم', 'خطا در دریافت تاریخچه گفتگو.', 'bot-message');
            }
            loadaichats();
        }

        document.getElementById('newChatBtn').addEventListener('click', () => {
            currentChatId = null;
            chatIdInput.value = '';
            history = [];
            historyInput.value = '[]';
            renderHeroState();
            loadaichats();
            if (window.innerWidth < 768) toggleSidebar();
        });

        async function createNewChat(message) {
            const formData = new URLSearchParams({ message: message });
            try {
                const response = await fetch('?action=create_chat', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.chat_id && data.chat_id > 0) {
                    currentChatId = data.chat_id;
                    chatIdInput.value = data.chat_id;
                    loadaichats();
                    return true;
                }
                return false;
            } catch (error) { return false; }
        }

        // Form submission SSE listener for real-time streaming
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            if (chatBox.querySelector('.hero-section')) {
                chatBox.innerHTML = '';
            }

            addMessage('شما', message, 'user-message');
            messageInput.value = '';

            let finalChatId = currentChatId;
            if (!currentChatId) {
                const created = await createNewChat(message);
                if (!created) {
                    addMessage('خطا', 'امکان ایجاد گفتگوی جدید وجود ندارد.', 'bot-message');
                    return;
                }
                finalChatId = currentChatId;
            }

            const botDiv = addMessage('زیرو', 'در حال پردازش...', 'bot-message', true);
            let botText = "";
            let firstChunk = true;

            const formData = new URLSearchParams({
                message: message,
                history: JSON.stringify(history),
                chat_id: finalChatId
            });

            try {
                const response = await fetch("index.php", { method: "POST", body: formData });
                if (!response.ok) throw new Error('HTTP ' + response.status);

                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value, { stream: true });
                    const lines = chunk.split("\n").filter(line => line.startsWith("data:"));

                    for (const line of lines) {
                        const data = line.replace("data:", "").trim();
                        if (data === "[DONE]") continue;
                        try {
                            const json = JSON.parse(data);
                            const delta = json.choices?.[0]?.delta?.content 
                                       || json.choices?.[0]?.text 
                                       || "";

                            if (delta) {
                                if (firstChunk) {
                                    botText = "";
                                    firstChunk = false;
                                }
                                botText += delta;
                                botDiv.innerHTML = formatMessage(botText);
                                chatBox.scrollTop = chatBox.scrollHeight;
                            }

                            if (json.error) {
                                botDiv.innerHTML = "خطا از API: " + (json.error.message || "نامشخص");
                                return;
                            }
                        } catch (err) {}
                    }
                }
            } catch (error) {
                botDiv.innerHTML = "خطا در دریافت پاسخ از سرور.";
                return;
            }

            if (!botText.trim()) {
                botDiv.innerHTML = "پاسخی دریافت نشد.";
                return;
            }

            history.push({ text: message, response: botText });
            historyInput.value = JSON.stringify(history);

            if (finalChatId) {
                const saveFormData = new URLSearchParams({
                    chat_id: finalChatId,
                    message: message,
                    response: botText
                });
                fetch('?action=save_message', { method: 'POST', body: saveFormData });
            }
            loadaichats();
        });

        document.addEventListener('DOMContentLoaded', () => {
            renderHeroState();
            loadaichats();
            const initChatId = new URLSearchParams(window.location.search).get('chat_id');
            if (initChatId) loadChat(initChatId);
        });

        function addMessage(sender, text, className, returnDiv = false) {
            const div = document.createElement('div');
            div.className = `chat-message ${className}`;
            div.innerHTML = formatMessage(text);
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight;
            if (returnDiv) return div;
        }
    </script>
</body>

</html>