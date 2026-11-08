<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

try {
    $db = forum_db();
    $action = (string) ($_GET['action'] ?? 'bootstrap');

    if ($action === 'bootstrap') {
        $rooms = $db->query(<<<'SQL'
            SELECT r.*,
                (SELECT COUNT(*) FROM messages mc WHERE mc.room_id = r.id) AS message_count,
                (SELECT body FROM messages lm WHERE lm.room_id = r.id ORDER BY lm.id DESC LIMIT 1) AS last_message,
                (SELECT user_name FROM messages lu WHERE lu.room_id = r.id ORDER BY lu.id DESC LIMIT 1) AS last_user,
                (SELECT created_at FROM messages lt WHERE lt.room_id = r.id ORDER BY lt.id DESC LIMIT 1) AS last_at
            FROM rooms r ORDER BY COALESCE(last_at, r.created_at) DESC
        SQL)->fetchAll();
        forum_json(['ok' => true, 'rooms' => $rooms, 'config' => ['pollInterval' => forum_config()['poll_interval_ms']]]);
    }

    if ($action === 'messages') {
        $roomId = max(1, (int) ($_GET['room_id'] ?? 1));
        $after = max(0, (int) ($_GET['after'] ?? 0));
        $roomStmt = $db->prepare('SELECT * FROM rooms WHERE id = ?');
        $roomStmt->execute([$roomId]);
        $room = $roomStmt->fetch();
        if (!$room) {
            forum_json(['ok' => false, 'message' => 'Ruang tidak ditemukan.'], 404);
        }
        $stmt = $db->prepare('SELECT * FROM messages WHERE room_id = ? AND id > ? ORDER BY id ASC LIMIT 250');
        $stmt->execute([$roomId, $after]);
        forum_json(['ok' => true, 'room' => $room, 'messages' => $stmt->fetchAll(), 'serverTime' => date(DATE_ATOM)]);
    }

    if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        forum_enforce_rate_limit($db);
        $data = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($data)) {
            forum_json(['ok' => false, 'message' => 'Format permintaan tidak valid.'], 400);
        }
        $body = trim((string) ($data['body'] ?? ''));
        $max = (int) forum_config()['max_message_length'];
        if ($body === '' || mb_strlen($body) > $max) {
            forum_json(['ok' => false, 'message' => "Pesan wajib diisi dan maksimal {$max} karakter."], 422);
        }
        $roomId = max(1, (int) ($data['room_id'] ?? 1));
        $userId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($data['user_id'] ?? 'guest')) ?: 'guest';
        $userName = trim(strip_tags((string) ($data['user_name'] ?? 'Tamu')));
        $userName = mb_substr($userName ?: 'Tamu', 0, 32);
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($data['avatar_color'] ?? '')) ? $data['avatar_color'] : '#119d53';
        $stmt = $db->prepare('INSERT INTO messages (room_id, user_id, user_name, avatar_color, body) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$roomId, $userId, $userName, $color, $body]);
        $message = $db->query('SELECT * FROM messages WHERE id = ' . (int) $db->lastInsertId())->fetch();
        forum_json(['ok' => true, 'message' => $message], 201);
    }

    forum_json(['ok' => false, 'message' => 'Endpoint tidak ditemukan.'], 404);
} catch (Throwable $error) {
    error_log('[Forum Chat] ' . $error->getMessage());
    forum_json(['ok' => false, 'message' => 'Server sedang bermasalah. Coba lagi sebentar.'], 500);
}
