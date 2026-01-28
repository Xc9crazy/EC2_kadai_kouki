<?php
require_once __DIR__ . '/includes/common.php';

// Require login
requireLogin();

header('Content-Type: application/json; charset=utf-8');

try {
    $dbh = getDB();
    
    // Get entries with user information using JOIN
    $stmt = $dbh->prepare("
        SELECT 
            e.id,
            e.user_id,
            e.body,
            e.image_filename,
            e.created_at,
            u.name as user_name,
            u.icon_filename as user_icon_filename
        FROM bbs_entries e
        INNER JOIN users u ON e.user_id = u.id
        ORDER BY e.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
    $entries = $stmt->fetchAll();
    
    // Format entries for JSON response
    $formattedEntries = array_map(function($entry) {
        return [
            'id' => (int)$entry['id'],
            'user_id' => (int)$entry['user_id'],
            'user_name' => $entry['user_name'],
            'user_icon_file_url' => $entry['user_icon_filename'] 
                ? '/upload/image/' . $entry['user_icon_filename'] 
                : '',
            'user_profile_url' => '/profile.php?user_id=' . $entry['user_id'],
            'body' => nl2br(htmlspecialchars($entry['body'], ENT_QUOTES, 'UTF-8')),
            'image_file_url' => $entry['image_filename'] 
                ? '/upload/image/' . $entry['image_filename'] 
                : '',
            'created_at' => formatDate($entry['created_at'])
        ];
    }, $entries);
    
    echo json_encode([
        'success' => true,
        'entries' => $formattedEntries
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Timeline JSON error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'データの取得に失敗しました'
    ], JSON_UNESCAPED_UNICODE);
}
