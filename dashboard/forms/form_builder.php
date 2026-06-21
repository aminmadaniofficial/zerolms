<?php
session_start();
require_once '../../db.php';
require_once '../../log.php';
date_default_timezone_set('Asia/Tehran');


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$form_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$form = null;
if ($form_id > 0) {
    $stmt = $pdo->prepare("SELECT title, content FROM forms WHERE id = ? AND created_by = ?");
    $stmt->execute([$form_id, $_SESSION['user_id']]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_form') {
    $form_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die(json_encode(['success' => false, 'error' => 'خطای CSRF']));
    }
    $title = trim($_POST['title'] ?? '');
    $description = $_POST['description'] ?? '';
    $raw_questions = $_POST['questions'] ?? [];
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'error' => 'عنوان فرم الزامی است']);
        exit;
    }

    
    $questions = [];
    foreach ($raw_questions as $index => $q) {
        $questions[$index] = [
            'label' => $q['label'] ?? '',
            'type' => $q['type'] ?? '',
            'required' => isset($q['required']) ? '1' : '0',
            'placeholder' => $q['placeholder'] ?? '',
            'maxlength' => $q['maxlength'] ?? '',
            'options' => $q['options'] ?? [],
            'accept' => $q['accept'] ?? '',
            'maxsize' => $q['maxsize'] ?? '',
            'min' => $q['min'] ?? '',
            'max' => $q['max'] ?? '',
            'step' => $q['step'] ?? ''
        ];
    }

    $content = json_encode([
        'title' => $title,
        'description' => $description,
        'questions' => $questions
    ], JSON_UNESCAPED_UNICODE);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'error' => 'خطا در تولید JSON: ' . json_last_error_msg()]);
        exit;
    }
    

$logo_url = null;

    try {
        $pdo->beginTransaction();
        $start_date = $_POST['start_date'] ? $_POST['start_date'] : null;
        $end_date = $_POST['end_date'] ? $_POST['end_date'] : null;
        if (!empty($_FILES['logo']['name'])) {
            $target_dir = "../../uploads/logos/";
            $logo_url = $target_dir . basename($_FILES['logo']['name']);
            move_uploaded_file($_FILES['logo']['tmp_name'], $logo_url);
        }
        if ($form_id > 0) {
            $stmt = $pdo->prepare("UPDATE forms SET title = ?, content = ?, logo_url = ? , start_date = ?, end_date = ? WHERE id = ? AND created_by = ?");
            $stmt->execute([$title, $content,$logo_url, $start_date, $end_date, $form_id, $_SESSION['user_id']]);
            
            $stmt = $pdo->prepare("SELECT code FROM forms WHERE id = ?");
            $stmt->execute([$form_id]);
            $code = $stmt->fetchColumn();
            addLog($pdo, $_SESSION['user_id'], 'ویرایش فرم', 'فرم', $form_id);
        } else {
            $code = bin2hex(random_bytes(16)); 
            $stmt = $pdo->prepare("INSERT INTO forms (title, code, content , logo_url, start_date, end_date, created_by, created_at) VALUES (?, ?,?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $code, $content,$logo_url, $start_date, $end_date, $_SESSION['user_id']]);
            $form_id = $pdo->lastInsertId();
            addLog($pdo, $_SESSION['user_id'], 'ساخت فرم', 'فرم', $form_id);
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'فرم با موفقیت ذخیره شد', 'code' => $code, 'form_id' => $form_id]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'خطا: ' . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فرم‌ساز</title>
    <link href="../../css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <link href="../../css/all.min.css" rel="stylesheet">
    <link href="https://vazir-fonts.ir/v1.0.0/vazir.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/fontawesome.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="../../js/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        .form-builder { max-width: 900px; margin: auto; }
        .question-types { margin-bottom: 20px; }
        .question-types button { margin: 5px; }
        .question-item { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: var(--card-bg); }
        .question-item .form-control { margin-bottom: 10px; }
        .option-list .input-group { margin-bottom: 10px; }
        .option-list .btn-remove-option { margin-left: 5px; }
        .question-actions { margin-top: 10px; }
        .tox-tinymce { border-radius: 8px; }
        .question-item.dragging {
            opacity: 0.5;
            border: 2px dashed #007bff;
        }
        .question-item:hover {
            cursor: move;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <span><a href="manage_forms.php" class="text-white"><i class="fas fa-arrow-right"></i> بازگشت به مدیریت فرم‌ها</a></span>
        <span>فرم‌ساز</span>
    </div>
    <div class="container mt-4 form-builder">
        <h3><?php echo $form_id > 0 ? 'ویرایش فرم' : 'ساخت فرم جدید'; ?></h3>
        <div id="formMessage"></div>
        <form id="formBuilderForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="save_form">
            <input type="hidden" name="id" value="<?php echo $form_id; ?>">
            <div class="mb-3">
                <label for="formTitle" class="form-label">عنوان فرم</label>
                <input type="text" class="form-control" id="formTitle" name="title" value="<?php echo htmlspecialchars($form['title'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
    <label for="formLogo" class="form-label">لوگوی فرم</label>
    <input type="file" class="form-control" id="formLogo" name="logo" accept="image/*">
</div>
            <div class="mb-3">
                <label for="formDescription" class="form-label">توضیحات فرم</label>
                <textarea id="formDescription" name="description"><?php echo htmlspecialchars($form['description'] ?? ''); ?></textarea>
            </div>
            <div class="question-types">
                <h5>افزودن سوال</h5>
                <button type="button" class="btn btn-secondary" data-type="text">متن کوتاه</button>
                <button type="button" class="btn btn-secondary" data-type="textarea">متن بلند</button>
                <button type="button" class="btn btn-secondary" data-type="multiple">چندگزینه‌ای</button>
                <button type="button" class="btn btn-secondary" data-type="checkbox">چک‌باکس</button>
                <button type="button" class="btn btn-secondary" data-type="dropdown">کشویی</button>
                <button type="button" class="btn btn-secondary" data-type="file">آپلود فایل</button>
                <button type="button" class="btn btn-secondary" data-type="date">تاریخ</button>
                <button type="button" class="btn btn-secondary" data-type="range">اسلایدر</button>
            </div>
            <div id="questionsContainer"></div>
            <div class="mb-3">
                <label for="startDate" class="form-label">تاریخ شروع</label>
                <input type="datetime-local" class="form-control" id="startDate" name="start_date">
            </div>
            <div class="mb-3">
                <label for="endDate" class="form-label">تاریخ پایان</label>
                <input type="datetime-local" class="form-control" id="endDate" name="end_date">
            </div>
            <button type="button" class="btn btn-info mt-3 ms-2" onclick="previewForm()">پیش‌نمایش</button>
            <button type="submit" class="btn btn-primary mt-3">ذخیره فرم</button>
        </form>
    </div>
    <script>
        let questionCounter = 0;

        function addQuestion(type) {
            questionCounter++;
            const container = document.getElementById('questionsContainer');
            const questionDiv = document.createElement('div');
            questionDiv.className = 'question-item';
            questionDiv.dataset.questionId = questionCounter;

            let html = `
                <div class="mb-3">
                    <label class="form-label">متن سوال</label>
                    <input type="text" class="form-control" name="questions[${questionCounter}][label]" placeholder="سوال خود را وارد کنید" required>
                </div>
                <input type="hidden" name="questions[${questionCounter}][type]" value="${type}">
                <div class="mb-3">
                    <label class="form-check-label">
                        <input type="checkbox" name="questions[${questionCounter}][required]" value="1"> پاسخ اجباری
                    </label>
                </div>`;

            if (type === 'text' || type === 'textarea') {
                html += `
                    <div class="mb-3">
                        <label class="form-label">Placeholder</label>
                        <input type="text" class="form-control" name="questions[${questionCounter}][placeholder]" placeholder="مثال: پاسخ خود را اینجا وارد کنید">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">حداکثر طول (کاراکتر)</label>
                        <input type="number" class="form-control" name="questions[${questionCounter}][maxlength]" min="1" max="1000">
                    </div>`;
            } else if (type === 'multiple' || type === 'checkbox' || type === 'dropdown') {
                html += `
                    <div class="option-list" data-question-id="${questionCounter}">
                        <h6>گزینه‌ها</h6>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" name="questions[${questionCounter}][options][]" placeholder="گزینه ۱" required>
                            <button type="button" class="btn btn-danger btn-remove-option">حذف</button>
                        </div>
                        <button type="button" class="btn btn-secondary btn-add-option" data-question-id="${questionCounter}">افزودن گزینه</button>
                    </div>`;
            } else if (type === 'file') {
                html += `
                    <div class="mb-3">
                        <label class="form-label">فرمت‌های مجاز (مثال: image/*,application/pdf)</label>
                        <input type="text" class="form-control" name="questions[${questionCounter}][accept]" placeholder="مثال: image/*,application/pdf">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">حداکثر حجم فایل (مگابایت)</label>
                        <input type="number" class="form-control" name="questions[${questionCounter}][maxsize]" min="1" max="100" value="5">
                    </div>`;
            } else if (type === 'range') {
                html += `
                    <div class="mb-3">
                        <label class="form-label">حداقل مقدار</label>
                        <input type="number" class="form-control" name="questions[${questionCounter}][min]" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">حداکثر مقدار</label>
                        <input type="number" class="form-control" name="questions[${questionCounter}][max]" value="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">گام (Step)</label>
                        <input type="number" class="form-control" name="questions[${questionCounter}][step]" value="1">
                    </div>`;
            }

            html += `
                <div class="question-actions">
                    <button type="button" class="btn btn-danger btn-remove-question">حذف سوال</button>
                </div>`;

            questionDiv.innerHTML = html;
            container.appendChild(questionDiv);

            
            questionDiv.querySelector('.btn-remove-question').addEventListener('click', () => {
                questionDiv.remove();
            });

            
            if (type === 'multiple' || type === 'checkbox' || type === 'dropdown') {
                questionDiv.querySelector('.btn-add-option').addEventListener('click', () => {
                    const optionList = questionDiv.querySelector('.option-list');
                    const optionDiv = document.createElement('div');
                    optionDiv.className = 'input-group mb-3';
                    optionDiv.innerHTML = `
                        <input type="text" class="form-control" name="questions[${questionCounter}][options][]" placeholder="گزینه جدید" required>
                        <button type="button" class="btn btn-danger btn-remove-option">حذف</button>`;
                    optionList.insertBefore(optionDiv, optionList.querySelector('.btn-add-option'));
                    optionDiv.querySelector('.btn-remove-option').addEventListener('click', () => {
                        optionDiv.remove();
                    });
                });

                
                questionDiv.querySelectorAll('.btn-remove-option').forEach(btn => {
                    btn.addEventListener('click', () => {
                        btn.parentElement.remove();
                    });
                });
            }
        }
        function previewForm() {
            tinymce.triggerSave();
            const title = document.getElementById('formTitle').value;
            const description = document.getElementById('formDescription').value;
            const questions = [];
            document.querySelectorAll('.question-item').forEach((item, index) => {
                const label = item.querySelector(`input[name="questions[${index + 1}][label]"]`).value;
                const type = item.querySelector(`input[name="questions[${index + 1}][type]"]`).value;
                const required = item.querySelector(`input[name="questions[${index + 1}][required]"]`).checked;
                const options = type === 'multiple' || type === 'checkbox' || type === 'dropdown' 
                    ? Array.from(item.querySelectorAll(`input[name="questions[${index + 1}][options][]"]`)).map(opt => opt.value) 
                    : [];
                questions.push({ label, type, required, options });
            });

            let previewHtml = `<h3>${title}</h3><div>${description}</div>`;
            questions.forEach(q => {
                previewHtml += `<div class="mb-3"><label>${q.label}${q.required ? '<span class="text-danger">*</span>' : ''}</label>`;
                if (q.type === 'text') previewHtml += `<input type="text" class="form-control" disabled>`;
                else if (q.type === 'textarea') previewHtml += `<textarea class="form-control" disabled></textarea>`;
                else if (q.type === 'multiple') {
                    q.options.forEach(opt => {
                        previewHtml += `<div class="form-check"><input type="radio" class="form-check-input" disabled><label>${opt}</label></div>`;
                    });
                }
                previewHtml += `</div>`;
            });

            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">پیش‌نمایش فرم</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">${previewHtml}</div>
                    </div>
                </div>`;
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            modal.addEventListener('hidden.bs.modal', () => modal.remove());
        }
        document.querySelectorAll('.question-types button').forEach(btn => {
            btn.addEventListener('click', () => {
                addQuestion(btn.dataset.type);
            });
        });

        document.getElementById('formBuilderForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            tinymce.triggerSave();
            const formData = new FormData(e.target);
            try {
                const response = await fetch('form_builder.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                const formMessage = document.getElementById('formMessage');
                formMessage.innerHTML = `
                    <div class="alert alert-${result.success ? 'success' : 'danger'} alert-dismissible fade show" role="alert">
                        ${result.success ? result.message : 'خطا: ' + result.error}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
                if (result.success && result.code) {
                    formMessage.innerHTML += `
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            لینک فرم: <a href="view_form.php?code=${result.code}" target="_blank">${window.location.origin}/forms/view_form.php?code=${result.code}</a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>`;
                }
                if (result.success && result.form_id) {
                    
                    window.location.href = 'manage_forms.php';
                }
            } catch (err) {
                document.getElementById('formMessage').innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        خطا: ${err.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>`;
            }
        });

        
        <?php if ($form && !empty($form['content'])): ?>
            console.log('Raw content from DB:', <?php echo json_encode($form['content']); ?>);
            let savedContent;
            try {
                savedContent = <?php echo json_encode(json_decode($form['content'], true), JSON_UNESCAPED_UNICODE); ?>;
                console.log('Parsed savedContent:', savedContent);

                tinymce.init({
                    selector: '#formDescription',
                    plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
                    toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code',
                    menubar: 'edit insert view format table tools',
                    height: 300,
                    directionality: 'rtl',
                    content_style: 'body { font-family: Vazir, Arial, sans-serif; }',
                    images_upload_url: 'upload_image.php',
                    automatic_uploads: true,
                    file_picker_types: 'image',
                    file_picker_callback: (cb, value, meta) => {
                        const input = document.createElement('input');
                        input.setAttribute('type', 'file');
                        input.setAttribute('accept', 'image/*');
                        input.onchange = function () {
                            const file = this.files[0];
                            const reader = new FileReader();
                            reader.onload = function () {
                                const id = 'blobid' + (new Date()).getTime();
                                const blobCache = tinymce.activeEditor.editorUpload.blobCache;
                                const base64 = reader.result.split(',')[1];
                                const blobInfo = blobCache.create(id, file, base64);
                                blobCache.add(blobInfo);
                                cb(blobInfo.blobUri(), { title: file.name });
                            };
                            reader.readAsDataURL(file);
                        };
                        input.click();
                    },
                    setup: (editor) => {
                        editor.on('change', () => {
                            document.getElementById('formDescription').value = editor.getContent();
                        });
                        editor.on('init', () => {
                            if (savedContent && savedContent.description) {
                                console.log('Loading description:', savedContent.description);
                                editor.setContent(savedContent.description);
                                document.getElementById('formDescription').value = savedContent.description;
                            } else {
                                console.log('No description found in savedContent');
                            }
                        });
                    }
                });

                if (savedContent && savedContent.questions && Object.keys(savedContent.questions).length > 0) {
                    console.log('Loading questions:', savedContent.questions);
                    Object.values(savedContent.questions).forEach((q, index) => {
                        console.log('Processing question:', q);
                        addQuestion(q.type);
                        const questionDiv = document.querySelector(`.question-item[data-question-id="${questionCounter}"]`);
                        if (!questionDiv) {
                            console.error(`Question div not found for questionCounter: ${questionCounter}`);
                            return;
                        }
                        if (q.label) questionDiv.querySelector(`input[name="questions[${questionCounter}][label]"]`).value = q.label;
                        if (q.required === '1') questionDiv.querySelector(`input[name="questions[${questionCounter}][required]"]`).checked = true;
                        if (q.placeholder) questionDiv.querySelector(`input[name="questions[${questionCounter}][placeholder]"]`).value = q.placeholder;
                        if (q.maxlength) questionDiv.querySelector(`input[name="questions[${questionCounter}][maxlength]"]`).value = q.maxlength;
                        if (q.accept) questionDiv.querySelector(`input[name="questions[${questionCounter}][accept]"]`).value = q.accept;
                        if (q.maxsize) questionDiv.querySelector(`input[name="questions[${questionCounter}][maxsize]"]`).value = q.maxsize;
                        if (q.min) questionDiv.querySelector(`input[name="questions[${questionCounter}][min]"]`).value = q.min;
                        if (q.max) questionDiv.querySelector(`input[name="questions[${questionCounter}][max]"]`).value = q.max;
                        if (q.step) questionDiv.querySelector(`input[name="questions[${questionCounter}][step]"]`).value = q.step;
                        if (q.options && q.options.length > 0) {
                            console.log('Loading options:', q.options);
                            questionDiv.querySelectorAll('.option-list .input-group').forEach((opt, index) => {
                                if (index > 0) opt.remove();
                            });
                            q.options.forEach((opt, index) => {
                                if (index === 0) {
                                    questionDiv.querySelector(`input[name="questions[${questionCounter}][options][]"]`).value = opt;
                                } else {
                                    const optionList = questionDiv.querySelector('.option-list');
                                    const optionDiv = document.createElement('div');
                                    optionDiv.className = 'input-group mb-3';
                                    optionDiv.innerHTML = `
                                        <input type="text" class="form-control" name="questions[${questionCounter}][options][]" value="${opt}" required>
                                        <button type="button" class="btn btn-danger btn-remove-option">حذف</button>`;
                                    optionList.insertBefore(optionDiv, optionList.querySelector('.btn-add-option'));
                                    optionDiv.querySelector('.btn-remove-option').addEventListener('click', () => {
                                        optionDiv.remove();
                                    });
                                }
                            });
                        }
                    });
                } else {
                    console.log('No questions found in savedContent');
                }
            } catch (err) {
                console.error('Error parsing savedContent:', err);
            }
        <?php else: ?>
            console.log('No form content to load');
            tinymce.init({
                selector: '#formDescription',
                plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
                toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code',
                menubar: 'edit insert view format table tools',
                height: 300,
                directionality: 'rtl',
                content_style: 'body { font-family: Vazir, Arial, sans-serif; }',
                images_upload_url: 'upload_image.php',
                automatic_uploads: true,
                file_picker_types: 'image',
                file_picker_callback: (cb, value, meta) => {
                    const input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/*');
                    input.onchange = function () {
                        const file = this.files[0];
                        const reader = new FileReader();
                        reader.onload = function () {
                            const id = 'blobid' + (new Date()).getTime();
                            const blobCache = tinymce.activeEditor.editorUpload.blobCache;
                            const base64 = reader.result.split(',')[1];
                            const blobInfo = blobCache.create(id, file, base64);
                            blobCache.add(blobInfo);
                            cb(blobInfo.blobUri(), { title: file.name });
                        };
                        reader.readAsDataURL(file);
                    };
                    input.click();
                },
                setup: (editor) => {
                    editor.on('change', () => {
                        document.getElementById('formDescription').value = editor.getContent();
                    });
                }
            });
        <?php endif; ?>
            function enableDragAndDrop() {
    const container = document.getElementById('questionsContainer');
    container.addEventListener('dragstart', (e) => {
        if (e.target.classList.contains('question-item')) {
            e.target.classList.add('dragging');
            e.dataTransfer.setData('text/plain', e.target.dataset.questionId);
        }
    });
    container.addEventListener('dragend', (e) => {
        e.target.classList.remove('dragging');
    });
    container.addEventListener('dragover', (e) => {
        e.preventDefault();
        const afterElement = getDragAfterElement(container, e.clientY);
        const draggable = document.querySelector('.dragging');
        if (afterElement == null) {
            container.appendChild(draggable);
        } else {
            container.insertBefore(draggable, afterElement);
        }
    });
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.question-item:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            }
            return closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.question-item').forEach(item => {
        item.setAttribute('draggable', true);
    });
    enableDragAndDrop();
});
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
