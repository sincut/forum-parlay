<?php
declare(strict_types=1);

require dirname(__DIR__) . '/pecintaparley.com/db.php';

function admin_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function admin_env(): array
{
    $path = __DIR__ . '/.env';
    $values = is_file($path) ? parse_ini_file($path, false, INI_SCANNER_RAW) : [];
    return is_array($values) ? $values : [];
}

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('pp_admin');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict']);
session_start();
$environment = admin_env();
$configuredHash = trim((string) ($environment['ADMIN_PASSWORD_HASH'] ?? ''));

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$error = '';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $attempts = (int) ($_SESSION['login_attempts'] ?? 0);
    $lastAttempt = (int) ($_SESSION['last_login_attempt'] ?? 0);
    if ($attempts >= 5 && time() - $lastAttempt < 300) {
        $error = 'Terlalu banyak percobaan. Coba lagi dalam beberapa menit.';
    } elseif ($configuredHash === '') {
        $error = 'Panel belum dikonfigurasi. Isi ADMIN_PASSWORD_HASH pada file .env.';
    } elseif (password_verify((string) ($_POST['password'] ?? ''), $configuredHash)) {
        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['login_attempts'] = 0;
        header('Location: /');
        exit;
    } else {
        $_SESSION['login_attempts'] = $attempts + 1;
        $_SESSION['last_login_attempt'] = time();
        $error = 'Password tidak sesuai.';
    }
}

$authenticated = ($_SESSION['admin_authenticated'] ?? false) === true;
if (!$authenticated):
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Admin — Pecinta Parlay</title><link rel="stylesheet" href="https://img.pecintaparley.com/admin/admin.css?v=1.0.0"></head><body class="login-body"><main class="login-card"><a class="admin-brand" href="https://pecintaparley.com">PP<span>•</span></a><p class="kicker">ADMIN PANEL</p><h1>Masuk ke panel</h1><p class="muted">Kelola ruang dan pesan Forum Pecinta Parlay.</p><?php if ($error !== ''): ?><p class="alert"><?= admin_h($error) ?></p><?php endif; ?><form method="post"><input type="hidden" name="action" value="login"><label>Password<input type="password" name="password" autocomplete="current-password" required autofocus></label><button type="submit">Masuk →</button></form></main></body></html>
<?php exit; endif;

$db = forum_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403); exit('Permintaan tidak valid.');
    }
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save_room') {
        $id = max(1, (int) ($_POST['room_id'] ?? 0));
        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 64);
        $description = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 500);
        $pinned = mb_substr(trim((string) ($_POST['pinned'] ?? '')), 0, 500);
        if ($name === '') { $error = 'Nama ruang wajib diisi.'; }
        else { $stmt = $db->prepare('UPDATE rooms SET name = ?, description = ?, pinned = ? WHERE id = ?'); $stmt->execute([$name, $description, $pinned, $id]); header('Location: /?saved=1'); exit; }
    }
    if ($action === 'delete_message') {
        $stmt = $db->prepare('DELETE FROM messages WHERE id = ?'); $stmt->execute([max(1, (int) ($_POST['message_id'] ?? 0))]); header('Location: /?deleted=1'); exit;
    }
}
$todaySql = forum_db_driver() === 'mysql'
    ? 'SELECT COUNT(*) FROM messages WHERE DATE(created_at) = CURRENT_DATE'
    : "SELECT COUNT(*) FROM messages WHERE date(created_at, 'localtime') = date('now', 'localtime')";
$stats = [
    'rooms' => (int) $db->query('SELECT COUNT(*) FROM rooms')->fetchColumn(),
    'messages' => (int) $db->query('SELECT COUNT(*) FROM messages')->fetchColumn(),
    'today' => (int) $db->query($todaySql)->fetchColumn(),
];
$rooms = $db->query('SELECT * FROM rooms ORDER BY id ASC')->fetchAll();
$messages = $db->query('SELECT m.*, r.name AS room_name FROM messages m JOIN rooms r ON r.id = m.room_id ORDER BY m.id DESC LIMIT 20')->fetchAll();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Dashboard Admin — Pecinta Parlay</title><link rel="stylesheet" href="https://img.pecintaparley.com/admin/admin.css?v=1.0.0"></head><body><header class="admin-header"><a class="admin-brand" href="https://pecintaparley.com">PP<span>•</span></a><div><a href="https://forum.pecintaparley.com" target="_blank" rel="noopener">Lihat forum ↗</a><a class="logout" href="?logout=1">Keluar</a></div></header><main class="dashboard"><div class="page-title"><div><p class="kicker">ADMIN PANEL</p><h1>Forum overview</h1></div><p class="muted">Kelola konten tanpa menyentuh database.</p></div><?php if (isset($_GET['saved'])): ?><p class="notice">Ruang berhasil diperbarui.</p><?php endif; ?><?php if (isset($_GET['deleted'])): ?><p class="notice">Pesan berhasil dihapus.</p><?php endif; ?><?php if ($error !== ''): ?><p class="alert"><?= admin_h($error) ?></p><?php endif; ?><section class="stats"><article><span>Ruang aktif</span><strong><?= $stats['rooms'] ?></strong></article><article><span>Total pesan</span><strong><?= $stats['messages'] ?></strong></article><article><span>Pesan hari ini</span><strong><?= $stats['today'] ?></strong></article></section><section class="admin-grid"><div class="panel"><div class="panel-title"><h2>Kelola ruang</h2><span><?= count($rooms) ?> ruang</span></div><?php foreach ($rooms as $room): ?><details><summary><span class="room-dot" style="--accent:<?= admin_h($room['accent']) ?>"><?= admin_h($room['icon']) ?></span><strong><?= admin_h($room['name']) ?></strong><span>Ubah</span></summary><form method="post" class="room-form"><input type="hidden" name="csrf" value="<?= admin_h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="save_room"><input type="hidden" name="room_id" value="<?= (int) $room['id'] ?>"><label>Nama ruang<input name="name" maxlength="64" value="<?= admin_h($room['name']) ?>" required></label><label>Deskripsi<textarea name="description" maxlength="500" rows="3"><?= admin_h($room['description']) ?></textarea></label><label>Pesan disematkan<textarea name="pinned" maxlength="500" rows="2"><?= admin_h($room['pinned']) ?></textarea></label><button type="submit">Simpan perubahan</button></form></details><?php endforeach; ?></div><div class="panel"><div class="panel-title"><h2>Pesan terbaru</h2><span>20 terbaru</span></div><div class="message-list"><?php foreach ($messages as $message): ?><article><div><b><?= admin_h($message['user_name']) ?></b><small><?= admin_h($message['room_name']) ?> · <?= admin_h($message['created_at']) ?></small><p><?= admin_h($message['body']) ?></p></div><form method="post" onsubmit="return confirm('Hapus pesan ini?')"><input type="hidden" name="csrf" value="<?= admin_h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="delete_message"><input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>"><button class="delete" type="submit">Hapus</button></form></article><?php endforeach; ?></div></div></section></main></body></html>
