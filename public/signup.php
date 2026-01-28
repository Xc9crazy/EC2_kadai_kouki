<?php
require_once __DIR__ . '/includes/common.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 【デバッグ】POSTデータの確認
    error_log("Signup POST received. CSRF token in POST: " . (isset($_POST['csrf_token']) ? 'yes' : 'no'));
    error_log("Signup POST received. CSRF token in SESSION: " . (isset($_SESSION['csrf_token']) ? 'yes' : 'no'));
    
    requireCsrfToken();
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validate input
    if (empty($name)) {
        $error = '名前を入力してください';
    } elseif (mb_strlen($name) > 100) {
        $error = '名前は100文字以内で入力してください';
    } elseif (empty($email)) {
        $error = 'メールアドレスを入力してください';
    } elseif (!isValidEmail($email)) {
        $error = '有効なメールアドレスを入力してください';
    } elseif (empty($password)) {
        $error = 'パスワードを入力してください';
    } elseif (!isValidPassword($password)) {
        $error = 'パスワードは8文字以上で、英字と数字を含める必要があります';
    } elseif ($password !== $password_confirm) {
        $error = 'パスワードが一致しません';
    } else {
        try {
            $dbh = getDB();
            
            // Check if email already exists
            $stmt = $dbh->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error = 'このメールアドレスは既に登録されています';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $dbh->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashedPassword
                ]);
                
                $success = true;
            }
        } catch (PDOException $e) {
            error_log("Signup error: " . $e->getMessage());
            $error = '会員登録処理でエラーが発生しました';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会員登録</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        button {
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
        button:hover {
            background-color: #45a049;
        }
        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }
        .info {
            text-align: center;
            margin-bottom: 20px;
            color: #666;
        }
        .info a {
            color: #4CAF50;
            text-decoration: none;
        }
        .info a:hover {
            text-decoration: underline;
        }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="info">
            すでにアカウントをお持ちの方は<a href="/login.php">ログイン</a>してください
        </div>
        
        <h1>会員登録</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= h($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                会員登録が完了しました！<br>
                <a href="/login.php">ログインページ</a>からログインしてください。
            </div>
        <?php else: ?>
            <form method="POST">
                <?= csrfTokenField() ?>
                
                <div class="form-group">
                    <label for="name">名前</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           required 
                           maxlength="100"
                           autocomplete="name"
                           value="<?= h($_POST['name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">メールアドレス</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required 
                           autocomplete="email"
                           value="<?= h($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">パスワード</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           minlength="8"
                           autocomplete="new-password">
                    <div class="help-text">8文字以上、英字と数字を含める必要があります</div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">パスワード（確認）</label>
                    <input type="password" 
                           id="password_confirm" 
                           name="password_confirm" 
                           required 
                           minlength="8"
                           autocomplete="new-password">
                </div>
                
                <button type="submit">登録</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>