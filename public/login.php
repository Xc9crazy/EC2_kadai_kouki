<?php
require_once __DIR__ . '/includes/common.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'メールアドレスとパスワードを入力してください';
    } elseif (!isValidEmail($email)) {
        $error = '有効なメールアドレスを入力してください';
    } else {
        try {
            $dbh = getDB();
            
            // Fetch user by email
            $stmt = $dbh->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            // Verify password
            if (!$user || !password_verify($password, $user['password'])) {
                // Use generic error message to prevent user enumeration
                $error = 'メールアドレスまたはパスワードが正しくありません';
                
                // Add small delay to prevent timing attacks
                usleep(rand(100000, 500000));
            } else {
                // Login successful
                setLoggedInUser($user['id']);
                
                // Log access
                try {
                    $stmt = $dbh->prepare("INSERT INTO access_logs (user_id, ip_address, user_agent) VALUES (:user_id, :ip, :ua)");
                    $stmt->execute([
                        ':user_id' => $user['id'],
                        ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                } catch (PDOException $e) {
                    error_log("Failed to log access: " . $e->getMessage());
                }
                
                redirect('/timeline.php');
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'ログイン処理でエラーが発生しました';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="info">
            初めての方は<a href="/signup.php">会員登録</a>をお願いします
        </div>
        
        <h1>ログイン</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= h($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?= csrfTokenField() ?>
            
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
                       autocomplete="current-password">
            </div>
            
            <button type="submit">ログイン</button>
        </form>
    </div>
</body>
</html>
