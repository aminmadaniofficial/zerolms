<?php
session_start();
date_default_timezone_set('Asia/Tehran');

require_once '../../db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login");
    exit();
}

$user_role = $_SESSION['role'] ?? 'student';
$course_id = (int) ($_GET['course_id'] ?? 0);

if ($course_id <= 0) {
    header("Location: ../index.php");
    exit();
}


if ($user_role !== 'admin') {
    if ($user_role === 'teacher') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ClassCourseTeachers WHERE teacher_id = :user_id AND class_course_id = :course_id");
        $stmt->execute(['user_id' => $_SESSION['user_id'], 'course_id' => $course_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM ClassCourses cc 
            JOIN users u ON u.class_id = cc.class_id 
            WHERE cc.id = :course_id AND u.id = :user_id
        ");
        $stmt->execute(['course_id' => $course_id, 'user_id' => $_SESSION['user_id']]);
    }
    if ($stmt->fetchColumn() == 0) {
        header("Location: ../index.php");
        exit();
    }
}


$stmt = $pdo->prepare("
    SELECT c.name AS class_name, cc.course_name 
    FROM ClassCourses cc 
    JOIN Classes c ON cc.class_id = c.id 
    WHERE cc.id = :course_id
");
$stmt->execute(['course_id' => $course_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
$course_name = $data ? htmlspecialchars($data['course_name']) : 'درس ناشناخته';
$class_name = $data ? htmlspecialchars($data['class_name']) : 'کلاس ناشناخته';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات درس - <?php echo $course_name; ?></title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon.png">
    <link rel="stylesheet" href="../assets/style.css">

    <style>
        :root {
            --primary-color: hsl(187, 91%, 50%);
            --secondary-color: hsl(25, 85%, 60%);
        }

        .container {
            padding: 20px;
        }

        .card {
            margin-bottom: 20px;
            border-color: var(--secondary-color);
        }

        .card-header {
            background: var(--primary-color);
            color: #fff;
        }

        .message-list,
        .notes-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .message-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .btn-back,
        .btn-send,
        .btn-upload,
        .btn-delete {
            background: var(--secondary-color);
            color: #fff;
        }

        .btn-delete-all {
            background: #dc3545;
            color: #fff;
        }

        .message-form textarea {
            resize: vertical;
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
            box-shadow: 0 -2px 10px rgba(1, 238, 255, 0.5);
            border-radius: 12px 12px 0 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 class="mb-4">جزئیات درس: <?php echo $course_name; ?> (<?php echo $class_name; ?>)</h2>
        <a href="../index.php" class="btn btn-back mb-4"><i class="fas fa-arrow-right me-2"></i> بازگشت به داشبورد</a>

        <!-- بخش پیام‌ها -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">پیام‌های درس</h5>
                <?php if ($user_role === 'teacher' || $user_role === 'admin'): ?>
                    <button class="btn btn-delete-all" id="deleteAllMessages"><i class="fas fa-trash me-2"></i>حذف تمامی
                        پیام‌ها</button>
                <?php endif; ?>
            </div>
            <div class="card-body message-list" id="messageList">
                <!-- پیام‌ها با AJAX لود می‌شن -->
            </div>
            <div class="card-footer">
                <form id="messageForm" class="message-form">
                    <div class="input-group">
                        <textarea class="form-control" id="messageContent" rows="3" placeholder="پیام خود را بنویسید..."
                            required></textarea>
                        <button type="submit" class="btn btn-send"><i class="fas fa-paper-plane me-2"></i>ارسال</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- بخش جزوات -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">جزوات درس</h5>
            </div>
            <div class="card-body notes-list" id="notesList">
                <!-- جزوات با AJAX لود می‌شن -->
            </div>
            <?php if ($user_role === 'teacher' || $user_role === 'admin'): ?>
                <div class="card-footer">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="input-group">
                            <input type="text" class="form-control" id="noteTitle" placeholder="عنوان جزوه" required>
                            <input type="file" class="form-control" id="noteFile" accept=".pdf,.docx,.jpg,.png" required>
                            <button type="submit" class="btn btn-upload"><i class="fas fa-upload me-2"></i>آپلود</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <footer>
        برنامه نویسی شده توسط
        <a href="https://aminmadani.ir" target="_blank">محمدامین مدنی محمدی</a>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const courseId = <?php echo (int) $course_id; ?>;
        const userRole = '<?php echo $user_role; ?>';
        console.log('Course ID:', courseId);

        
        document.getElementById('messageForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const content = document.getElementById('messageContent').value.trim();
            if (!content) {
                alert('لطفاً متن پیام را وارد کنید');
                return;
            }

            try {
                const response = await fetch('send_message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `class_course_id=${courseId}&content=${encodeURIComponent(content)}`
                });
                const result = await response.json();
                console.log('Message response:', result);
                if (result.success) {
                    document.getElementById('messageContent').value = '';
                    loadMessages();
                } else {
                    alert('خطا در ارسال پیام: ' + (result.error || 'نامشخص'));
                }
            } catch (err) {
                console.error('Message fetch error:', err);
                alert('خطا در ارتباط با سرور: ' + err.message);
            }
        });

        
        async function loadMessages() {
            try {
                const response = await fetch(`get_messages.php?class_course_id=${courseId}`);
                const messages = await response.json();
                console.log('Messages:', messages);
                const messageList = document.getElementById('messageList');
                messageList.innerHTML = messages.length ? messages.map(m => `
                    <div class="message-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${m.name}</strong> (${m.created_at}):<br>
                            ${m.content}
                        </div>
                        ${userRole === 'teacher' || userRole === 'admin' ? `
                            <button class="btn btn-delete btn-sm" data-message-id="${m.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                `).join('') : '<p class="text-center">پیامی برای این درس وجود ندارد.</p>';
                messageList.scrollTop = messageList.scrollHeight;

                
                document.querySelectorAll('.btn-delete').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (!confirm('آیا مطمئن هستید که می‌خواهید این پیام را حذف کنید؟')) return;
                        const messageId = btn.dataset.messageId;
                        try {
                            const response = await fetch('delete_message.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `message_id=${messageId}`
                            });
                            const result = await response.json();
                            console.log('Delete message response:', result);
                            if (result.success) {
                                loadMessages();
                            } else {
                                alert('خطا در حذف پیام: ' + (result.error || 'نامشخص'));
                            }
                        } catch (err) {
                            console.error('Delete message error:', err);
                            alert('خطا در ارتباط با سرور: ' + err.message);
                        }
                    });
                });
            } catch (err) {
                console.error('Messages fetch error:', err);
                document.getElementById('messageList').innerHTML = '<p class="text-center text-danger">خطا در بارگذاری پیام‌ها: ' + err.message + '</p>';
            }
        }

        
        <?php if ($user_role === 'teacher' || $user_role === 'admin'): ?>
            document.getElementById('deleteAllMessages').addEventListener('click', async () => {
                if (!confirm('آیا مطمئن هستید که می‌خواهید تمام پیام‌های این درس را حذف کنید؟')) return;
                try {
                    const response = await fetch('delete_all_messages.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `class_course_id=${courseId}`
                    });
                    const result = await response.json();
                    console.log('Delete all messages response:', result);
                    if (result.success) {
                        loadMessages();
                    } else {
                        alert('خطا در حذف پیام‌ها: ' + (result.error || 'نامشخص'));
                    }
                } catch (err) {
                    console.error('Delete all messages error:', err);
                    alert('خطا در ارتباط با سرور: ' + err.message);
                }
            });
        <?php endif; ?>

        setInterval(loadMessages, 5000);
        loadMessages();

        
        async function loadNotes() {
            try {
                const response = await fetch(`get_notes.php?class_course_id=${courseId}`);
                const notes = await response.json();
                console.log('Notes:', notes);
                const notesList = document.getElementById('notesList');
                notesList.innerHTML = notes.length ? '<ul class="list-group">' + notes.map(n => `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <a href="../../${n.file_path}" download>${n.title}</a> (${n.created_at})
                        </div>
                        ${userRole === 'teacher' || userRole === 'admin' ? `
                            <button class="btn btn-delete btn-sm" data-note-id="${n.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </li>
                `).join('') + '</ul>' : '<p class="text-center">جزوه‌ای برای این درس وجود ندارد.</p>';

                
                document.querySelectorAll('.btn-delete').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (!confirm('آیا مطمئن هستید که می‌خواهید این جزوه را حذف کنید؟')) return;
                        const noteId = btn.dataset.noteId;
                        try {
                            const response = await fetch('delete_note.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `note_id=${noteId}`
                            });
                            const result = await response.json();
                            console.log('Delete note response:', result);
                            if (result.success) {
                                loadNotes();
                            } else {
                                alert('خطا در حذف جزوه: ' + (result.error || 'نامشخص'));
                            }
                        } catch (err) {
                            console.error('Delete note error:', err);
                            alert('خطا در ارتباط با سرور: ' + err.message);
                        }
                    });
                });
            } catch (err) {
                console.error('Notes fetch error:', err);
                document.getElementById('notesList').innerHTML = '<p class="text-center text-danger">خطا در بارگذاری جزوات: ' + err.message + '</p>';
            }
        }

        
        <?php if ($user_role === 'teacher' || $user_role === 'admin'): ?>
            document.getElementById('uploadForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const title = document.getElementById('noteTitle').value.trim();
                const file = document.getElementById('noteFile').files[0];
                if (!title || !file) {
                    alert('لطفاً عنوان و فایل را وارد کنید');
                    return;
                }

                const formData = new FormData();
                formData.append('class_course_id', courseId);
                console.log('Sending class_course_id:', courseId);
                formData.append('title', title);
                formData.append('note_file', file);

                try {
                    const response = await fetch('upload_note.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    console.log('Upload response:', result);
                    if (result.success) {
                        document.getElementById('noteTitle').value = '';
                        document.getElementById('noteFile').value = '';
                        loadNotes();
                    } else {
                        alert('خطا در آپلود جزوه: ' + (result.error || 'نامشخص'));
                    }
                } catch (err) {
                    console.error('Upload fetch error:', err);
                    alert('خطا در ارتباط با سرور: ' + err.message);
                }
            });
        <?php endif; ?>

        loadNotes();
    </script>
</body>

</html>