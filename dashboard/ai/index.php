<?php
session_start();
require_once '../../db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit;
}

$user_id = $_SESSION['user_id'];
$api_key = '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'; 


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
    $title = substr($message, 0, 50) . '...';
    return createChat($pdo, $user_id, $title);
}

function saveMessage($pdo, $chat_id, $sender, $content)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO aimessages (chat_id, sender, content) VALUES (?, ?, ?)");
        $stmt->execute([$chat_id, $sender, $content]);
    } catch (PDOException $e) {
        error_log("DB Save Error: " . $e->getMessage());
    }
}

function loadChatHistory($pdo, $chat_id)
{
    try {
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


if (isset($_GET['action']) && $_GET['action'] === 'get_aichats') {
    $chats = getUserChats($GLOBALS['pdo'], $user_id);
    header('Content-Type: application/json');
    echo json_encode($chats);
    exit;
}


if (isset($_GET['action']) && $_GET['action'] === 'create_chat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'] ?? '';
    if (empty($message)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'پیام خالی']);
        exit;
    }
    $title = substr($message, 0, 50) . '...';
    $chat_id = createChat($GLOBALS['pdo'], $user_id, $title);
    header('Content-Type: application/json');
    echo json_encode(['chat_id' => $chat_id ?? 0]);
    exit;
}


if (isset($_GET['action']) && $_GET['action'] === 'save_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $chat_id = $_POST['chat_id'] ?? '';
    $message = $_POST['message'] ?? '';
    $response = $_POST['response'] ?? '';
    if ($chat_id && !empty($message) && !empty($response)) {
        saveMessage($GLOBALS['pdo'], $chat_id, 'user', $message);
        saveMessage($GLOBALS['pdo'], $chat_id, 'bot', $response);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'داده ناقص']);
    }
    exit;
}


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


$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$chat_id = $_GET['chat_id'] ?? null;
if ($chat_id && !isset($_GET['action']) && $is_ajax) {
    $history = loadChatHistory($GLOBALS['pdo'], $chat_id);
    header('Content-Type: application/json');
    echo json_encode(['chat_id' => $chat_id, 'history' => $history]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    $message = $_POST['message'] ?? '';
    $history_json = $_POST['history'] ?? '[]';
    $chat_id = $_POST['chat_id'] ?? null;

    if (empty($message)) {
        echo "data: " . json_encode(['error' => 'پیام خالیه! 😅']) . "\n\n";
        flush();
        exit;
    }

    $current_chat_id = getOrCreateChat($GLOBALS['pdo'], $user_id, $message, $chat_id);

    $history = json_decode($history_json, true);

    $messages = [
        [
            'role' => 'system',
            'content' => 'شما یک دستیار مفید و دقیق هستید. به سوال فعلی مستقیم جواب بده. شوخ‌طبع باش، بی‌احترامی نکن. اسم تو زیرو هست و سازنده‌ات محمدامین مدنی محمدی هست. تو گروک نیستی و زیرو هستی و قرار نیست اگر کسی تقلب خواست بهش برسونی و همیشه خلاصه حرف بزن ، فقط به سوال فعلی جواب بده و کانتکست ها جواب نده فقط بدون که موضوع اون هست'
        ]
    ];

    $recent_history = array_slice($history, -10);
    foreach ($recent_history as $msg) {
        $messages[] = ['role' => 'user', 'content' => $msg['text']];
        $messages[] = ['role' => 'assistant', 'content' => $msg['response']];
    }
    $messages[] = ['role' => 'user', 'content' => $message];

    $data = json_encode([
        'model' => 'z-ai/glm-4.5-air:free',
        'messages' => $messages,
        'stream' => true
    ]);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>هوش مصنوعی</title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://vazir-fonts.ir/v1.0.0/vazir.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 300px;
            background: var(---card-bg);
            height: 100vh;
            overflow-y: auto;
            padding: 20px;
            border-left: 1px solid var(--card-bg);
            position: fixed;
            z-index: 4;

        }

        .main-content {
            flex: 1;
            padding: 20px;
            margin-right: 300px;
        }

        .chat-list {
            list-style: none;
            padding: 0;
        }

        .chat-item {
            padding: 10px;
            border: 1px solid #ddd;
            margin-bottom: 5px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-item:hover,
        .chat-item.active {
            background: #007bff;
            color: black;
        }

        .chat-title {
            flex-grow: 1;
        }

        .chat-actions {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 2px 5px;
            font-size: 0.8em;
        }

        .chat-box {
            height: 400px;
            overflow-y: auto;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            color: black;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .chat-message {
            margin: 8px 0;
            padding: 12px;
            border-radius: 8px;
            transition: all 0.3s;
            white-space: pre-wrap;
            line-height: 1.5;
        }

        .user-message {
            background: #007bff;
            color: white;
            margin-left: 20%;
        }

        .bot-message {
            background: #e9ecef;
            color: black;
            margin-right: 20%;
        }

        .chat-message:hover {
            transform: translateY(-2px);
        }

        .bot-message h3 {
            font-size: 1.2em;
            margin: 10px 0 5px;
            color: #333;
        }

        .bot-message strong {
            font-weight: bold;
            color: black;
        }

        .bot-message br {
            display: block;
            margin: 5px 0;
        }

        #newChatBtn {
            width: 100%;
            margin-bottom: 20px;
        }

        footer {
            position: fixed;
            bottom: 0;
            width: 98%;
            right: 1%;
            margin: auto;
            text-align: center;
            padding: 12px 0;
            background-color: #1e1e1eff;
            color: #ffffff;
            font-weight: bold;
            z-index: -1;
            box-shadow: 0 -2px 10px rgba(1, 238, 255, 0.5);
            border-radius: 12px 12px 0 0;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h4>چت‌های شما</h4>
        <button id="newChatBtn" class="btn btn-success"><i class="fas fa-plus"></i> چت جدید</button>
        <ul id="chatList" class="chat-list"></ul>
    </div>
    <div class="main-content">
        <h3 style="text-align:center;"><i class="fas fa-robot"></i> هوش مصنوعی زیرو</h3>
        <div id="chatBox" class="chat-box mb-3"></div>
        <form id="chatForm">
            <input type="hidden" id="history" name="history" value="[]">
            <input type="hidden" id="chatIdInput" name="chat_id" value="">
            <div class="input-group">
                <input type="text" id="messageInput" class="form-control" placeholder="سوالت رو بپرس...">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> ارسال</button>
            </div>
        </form>
    </div>

    <script>
        const chatBox = document.getElementById('chatBox');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const historyInput = document.getElementById('history');
        const chatIdInput = document.getElementById('chatIdInput');
        const chatList = document.getElementById('chatList');
        let currentChatId = null;
        let history = [];

        
        function formatMessage(text) {
            
            text = text.replace(/^### (.*$)/gm, '<h3>$1</h3>');
            
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            
            text = text.replace(/\n/g, '<br>');
            
            return text.replace(/<br>/g, '<br><span style="display: block; margin-bottom: 5px;"></span>');
        }

        
        async function loadaichats() {
            try {
                const response = await fetch('?action=get_aichats');
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const aichats = await response.json();
                chatList.innerHTML = '';
                aichats.forEach(chat => {
                    const li = document.createElement('li');
                    li.className = 'chat-item';
                    li.setAttribute('data-chat-id', chat.id);

                    const titleSpan = document.createElement('span');
                    titleSpan.className = 'chat-title';
                    titleSpan.innerHTML = `<strong>${chat.title}</strong><br><small>${new Date(chat.created_at).toLocaleDateString('fa-IR')}</small>`;

                    const actionsDiv = document.createElement('div');
                    actionsDiv.className = 'chat-actions';

                    
                    const editBtn = document.createElement('button');
                    editBtn.className = 'btn btn-sm btn-warning';
                    editBtn.innerHTML = '<i class="fas fa-edit"></i>';
                    editBtn.title = 'تغییر نام';
                    editBtn.addEventListener('click', (e) => {
                        e.stopPropagation(); 
                        const newTitle = prompt('نام جدید چت:', chat.title);
                        if (newTitle && newTitle.trim()) {
                            updateChatTitle(chat.id, newTitle.trim());
                        }
                    });

                    
                    const deleteBtn = document.createElement('button');
                    deleteBtn.className = 'btn btn-sm btn-danger';
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    deleteBtn.title = 'حذف چت';
                    deleteBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (confirm('آیا مطمئنی می‌خوای این چت و تمام پیام‌هاش رو حذف کنی؟')) {
                            deleteChat(chat.id);
                        }
                    });

                    actionsDiv.appendChild(editBtn);
                    actionsDiv.appendChild(deleteBtn);

                    li.appendChild(titleSpan);
                    li.appendChild(actionsDiv);

                    li.addEventListener('click', (e) => {
                        if (!e.target.closest('.chat-actions')) loadChat(chat.id); 
                    });

                    if (currentChatId == chat.id) li.classList.add('active');
                    chatList.appendChild(li);
                });
            } catch (error) {
                console.error('خطا در لود چت‌ها:', error);
                chatList.innerHTML = '<li>هیچ چتی وجود ندارد یا خطا در DB</li>';
            }
        }

        
        async function updateChatTitle(chatId, newTitle) {
            const formData = new URLSearchParams({ chat_id: chatId, title: newTitle });
            try {
                const response = await fetch('?action=update_chat_title', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    loadaichats(); 
                    alert('عنوان بروزرسانی شد!');
                } else {
                    alert('خطا در بروزرسانی: ' + (data.error || 'نامشخص'));
                }
            } catch (error) {
                console.error('Update Title Error:', error);
                alert('خطا در اتصال');
            }
        }

        
        async function deleteChat(chatId) {
            const formData = new URLSearchParams({ chat_id: chatId });
            try {
                const response = await fetch('?action=delete_chat', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    loadaichats(); 
                    
                    if (currentChatId == chatId) {
                        document.getElementById('newChatBtn').click();
                    }
                    alert('چت حذف شد!');
                } else {
                    alert('خطا در حذف: ' + (data.error || 'نامشخص'));
                }
            } catch (error) {
                console.error('Delete Error:', error);
                alert('خطا در اتصال');
            }
        }

        
        async function loadChat(chatId) {
            currentChatId = chatId;
            chatIdInput.value = chatId;
            history = [];
            chatBox.innerHTML = '';

            const url = new URL(window.location);
            url.searchParams.set('chat_id', chatId);
            window.history.pushState({}, '', url);

            try {
                const response = await fetch(`?chat_id=${chatId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const data = await response.json();
                history = data.history || [];

                
                history.forEach(msg => {
                    addMessage('شما', msg.text, 'user-message');
                    addMessage('دستیار', formatMessage(msg.response), 'bot-message'); 
                });
                chatBox.scrollTop = chatBox.scrollHeight;
            } catch (error) {
                console.error('خطا در لود history:', error);
                addMessage('خطا', 'نمی‌تونم history رو لود کنم', 'bot-message');
            }

            document.querySelectorAll('.chat-item').forEach(item => item.classList.remove('active'));
            document.querySelector(`[data-chat-id="${chatId}"]`)?.classList.add('active');
            loadaichats();
        }

        
        document.getElementById('newChatBtn').addEventListener('click', () => {
            currentChatId = null;
            chatIdInput.value = '';
            history = [];
            chatBox.innerHTML = '';
            historyInput.value = '[]';
            const url = new URL(window.location);
            url.searchParams.delete('chat_id');
            window.history.pushState({}, '', url);
            loadaichats();
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
                } else {
                    console.error('خطا در ایجاد چت:', data.error);
                    return false;
                }
            } catch (error) {
                console.error('AJAX Create Error:', error);
                return false;
            }
        }

        
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!messageInput.value.trim()) return;

            const message = messageInput.value.trim();
            addMessage('شما', message, 'user-message');
            messageInput.value = '';

            let finalChatId = currentChatId;

            if (!currentChatId) {
                const created = await createNewChat(message);
                if (!created) {
                    addMessage('خطا', 'نمی‌تونم چت جدید بسازم', 'bot-message');
                    return;
                }
                finalChatId = currentChatId;
            }

            const botDiv = addMessage('دستیار', 'در حال پردازش متن ...', 'bot-message', true);

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
                    console.log('Chunk دریافتی:', chunk.substring(0, 200) + '...');

                    const lines = chunk.split("\n").filter(line => line.startsWith("data:"));

                    for (const line of lines) {
                        const data = line.replace("data:", "").trim();
                        if (data === "[DONE]") continue;
                        try {
                            const json = JSON.parse(data);
                            const delta = json.choices?.[0]?.delta?.content || "";
                            if (delta) {
                                if (firstChunk) {
                                    botText = "";
                                    firstChunk = false;
                                }
                                botText += delta;
                                botDiv.innerHTML = "دستیار: " + formatMessage(botText); 
                                chatBox.scrollTop = chatBox.scrollHeight;
                            }
                            if (json.error) {
                                botDiv.innerHTML = "دستیار: خطا - " + json.error.message;
                                return;
                            }
                        } catch { }
                    }
                }
            } catch (error) {
                console.error('Stream Error:', error);
                botDiv.innerHTML = "دستیار: خطا در اتصال - " + error.message;
                return;
            }

            if (!botText.trim()) {
                botDiv.innerHTML = "دستیار: پاسخی از AI نیومد";
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
                fetch('?action=save_message', { method: 'POST', body: saveFormData })
                    .catch(err => console.error('Save DB Error:', err));
            }

            loadaichats();
        });

        
        document.addEventListener('DOMContentLoaded', () => {
            loadaichats();
            const urlParams = new URLSearchParams(window.location.search);
            const initChatId = urlParams.get('chat_id');
            if (initChatId) loadChat(initChatId);
        });

        function addMessage(sender, text, className, returnDiv = false) {
            const div = document.createElement('div');
            div.className = `chat-message ${className}`;
            if (className.includes('bot-message')) {
                div.innerHTML = `${sender}: ${formatMessage(text)}`; 
            } else {
                div.textContent = `${sender}: ${text}`; 
            }
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight;
            if (returnDiv) return div;
        }
    </script>
    <footer>
        برنامه نویسی شده توسط
        <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
</body>

</html>