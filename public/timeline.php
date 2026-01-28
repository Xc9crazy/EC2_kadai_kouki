<?php
require_once __DIR__ . '/includes/common.php';

// Require login
requireLogin();

// Get current user
$currentUser = getUserById(getCurrentUserId());
if (!$currentUser) {
    logout();
    redirect('/login.php');
}

$error = '';
$success = '';

// Handle post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['body'])) {
    requireCsrfToken();
    
    $body = trim($_POST['body'] ?? '');
    $imageFilename = null;
    
    // Validate input
    if (empty($body)) {
        $error = '投稿内容を入力してください';
    } elseif (mb_strlen($body) > 1000) {
        $error = '投稿は1000文字以内で入力してください';
    } else {
        // Handle image upload
        if (!empty($_POST['image_base64'])) {
            $imageFilename = saveBase64Image($_POST['image_base64']);
            if ($imageFilename === false) {
                $error = '画像のアップロードに失敗しました';
            }
        }
        
        if (empty($error)) {
            try {
                $dbh = getDB();
                $stmt = $dbh->prepare("INSERT INTO bbs_entries (user_id, body, image_filename) VALUES (:user_id, :body, :image_filename)");
                $stmt->execute([
                    ':user_id' => getCurrentUserId(),
                    ':body' => $body,
                    ':image_filename' => $imageFilename
                ]);
                
                // Redirect to prevent form resubmission
                redirect('/timeline.php?posted=1');
            } catch (PDOException $e) {
                error_log("Post creation error: " . $e->getMessage());
                $error = '投稿の作成に失敗しました';
            }
        }
    }
}

// Check for success message
if (isset($_GET['posted'])) {
    $success = '投稿しました';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タイムライン</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        .header {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info {
            font-size: 14px;
            color: #555;
        }
        .nav-links a {
            color: #4CAF50;
            text-decoration: none;
            margin-left: 15px;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .post-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
        }
        textarea:focus {
            outline: none;
            border-color: #4CAF50;
        }
        .image-upload {
            margin: 15px 0;
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 500;
        }
        .submit-btn:hover {
            background-color: #45a049;
        }
        .submit-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #c62828;
        }
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #2e7d32;
        }
        .timeline {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .entry {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        .entry:last-child {
            border-bottom: none;
        }
        .entry-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .user-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        .user-name {
            font-weight: 600;
            color: #333;
            text-decoration: none;
        }
        .user-name:hover {
            text-decoration: underline;
        }
        .entry-date {
            font-size: 12px;
            color: #999;
            margin-left: auto;
        }
        .entry-body {
            margin-top: 10px;
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #333;
        }
        .entry-image {
            margin-top: 10px;
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            display: block;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        @media (max-width: 600px) {
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            .nav-links {
                margin-top: 10px;
            }
            .nav-links a {
                margin-left: 0;
                margin-right: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="user-info">
                <?= h($currentUser['name']) ?> さんでログイン中
            </div>
            <div class="nav-links">
                <a href="/setting/index.php">設定</a>
                <a href="/users.php">会員一覧</a>
                <a href="/logout.php">ログアウト</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="error"><?= h($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= h($success) ?></div>
        <?php endif; ?>
        
        <div class="post-form">
            <form method="POST" id="postForm">
                <?= csrfTokenField() ?>
                <textarea name="body" 
                          id="bodyInput" 
                          placeholder="いまどうしてる？" 
                          required 
                          maxlength="1000"></textarea>
                
                <div class="image-upload">
                    <input type="file" 
                           accept="image/*" 
                           id="imageInput">
                </div>
                
                <input type="hidden" name="image_base64" id="imageBase64Input">
                <canvas id="imageCanvas" style="display: none;"></canvas>
                
                <button type="submit" class="submit-btn" id="submitBtn">投稿する</button>
            </form>
        </div>
        
        <div class="timeline">
            <div id="entriesArea" class="loading">
                読み込み中...
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const entriesArea = document.getElementById('entriesArea');
    const imageInput = document.getElementById('imageInput');
    const imageBase64Input = document.getElementById('imageBase64Input');
    const canvas = document.getElementById('imageCanvas');
    const submitBtn = document.getElementById('submitBtn');
    
    // Load timeline entries
    loadEntries();
    
    // Image upload handler
    imageInput.addEventListener('change', () => {
        if (imageInput.files.length < 1) return;
        
        const file = imageInput.files[0];
        if (!file.type.startsWith('image/')) return;
        
        submitBtn.disabled = true;
        submitBtn.textContent = '画像処理中...';
        
        const reader = new FileReader();
        const image = new Image();
        
        reader.onload = () => {
            image.onload = () => {
                const originalWidth = image.naturalWidth;
                const originalHeight = image.naturalHeight;
                const maxLength = 1000;
                
                if (originalWidth <= maxLength && originalHeight <= maxLength) {
                    canvas.width = originalWidth;
                    canvas.height = originalHeight;
                } else if (originalWidth > originalHeight) {
                    canvas.width = maxLength;
                    canvas.height = maxLength * originalHeight / originalWidth;
                } else {
                    canvas.width = maxLength * originalWidth / originalHeight;
                    canvas.height = maxLength;
                }
                
                const context = canvas.getContext('2d');
                context.drawImage(image, 0, 0, canvas.width, canvas.height);
                imageBase64Input.value = canvas.toDataURL();
                
                submitBtn.disabled = false;
                submitBtn.textContent = '投稿する';
            };
            image.src = reader.result;
        };
        reader.readAsDataURL(file);
    });
    
    function loadEntries() {
        fetch('/timeline_json.php')
            .then(response => response.json())
            .then(data => {
                if (!data.entries || data.entries.length === 0) {
                    entriesArea.innerHTML = '<div class="loading">投稿がまだありません</div>';
                    return;
                }
                
                entriesArea.innerHTML = '';
                data.entries.forEach(entry => {
                    entriesArea.appendChild(createEntryElement(entry));
                });
            })
            .catch(error => {
                console.error('Error loading entries:', error);
                entriesArea.innerHTML = '<div class="loading">読み込みに失敗しました</div>';
            });
    }
    
    function createEntryElement(entry) {
        const div = document.createElement('div');
        div.className = 'entry';
        
        const header = document.createElement('div');
        header.className = 'entry-header';
        
        if (entry.user_icon_file_url) {
            const icon = document.createElement('img');
            icon.className = 'user-icon';
            icon.src = entry.user_icon_file_url;
            icon.alt = entry.user_name;
            header.appendChild(icon);
        }
        
        const nameLink = document.createElement('a');
        nameLink.className = 'user-name';
        nameLink.href = entry.user_profile_url;
        nameLink.textContent = entry.user_name;
        header.appendChild(nameLink);
        
        const date = document.createElement('div');
        date.className = 'entry-date';
        date.textContent = entry.created_at;
        header.appendChild(date);
        
        div.appendChild(header);
        
        const body = document.createElement('div');
        body.className = 'entry-body';
        body.innerHTML = entry.body;
        div.appendChild(body);
        
        if (entry.image_file_url) {
            const img = document.createElement('img');
            img.className = 'entry-image';
            img.src = entry.image_file_url;
            img.alt = '投稿画像';
            div.appendChild(img);
        }
        
        return div;
    }
});
</script>
</body>
</html>
