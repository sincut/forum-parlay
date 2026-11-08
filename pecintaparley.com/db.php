<?php
declare(strict_types=1);

function forum_config(): array
{
    static $config;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
        date_default_timezone_set($config['timezone']);
    }
    return $config;
}

function forum_db_driver(): string
{
    $driver = (string) (forum_config()['database']['driver'] ?? 'mysql');
    if (!in_array($driver, ['mysql', 'sqlite'], true)) {
        throw new RuntimeException('DB_DRIVER harus berisi mysql atau sqlite.');
    }
    return $driver;
}

function forum_db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $database = forum_config()['database'];
    $driver = forum_db_driver();
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if ($driver === 'mysql') {
        foreach (['host', 'name', 'user'] as $required) {
            if (trim((string) ($database[$required] ?? '')) === '') {
                throw new RuntimeException('Konfigurasi MySQL belum lengkap. Isi DB_HOST, DB_NAME, dan DB_USER pada .env.');
            }
        }
        $charset = preg_match('/^[a-zA-Z0-9_]+$/', (string) $database['charset'])
            ? (string) $database['charset']
            : 'utf8mb4';
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            (string) $database['host'],
            (int) $database['port'],
            (string) $database['name'],
            $charset
        );
        $pdo = new PDO($dsn, (string) $database['user'], (string) $database['password'], $options);
        $pdo->exec("SET time_zone = '+07:00'");
    } else {
        $path = (string) $database['sqlite_path'];
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder data tidak dapat dibuat.');
        }
        $pdo = new PDO('sqlite:' . $path, null, null, $options);
        $pdo->exec('PRAGMA journal_mode = WAL; PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
    }

    forum_migrate($pdo);
    return $pdo;
}

function forum_migrate(PDO $pdo): void
{
    if (forum_db_driver() === 'mysql') {
        $statements = [
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS rooms (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(64) NOT NULL,
                description TEXT NOT NULL,
                icon VARCHAR(32) NOT NULL DEFAULT '💬',
                accent CHAR(7) NOT NULL DEFAULT '#12a454',
                pinned TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS messages (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                room_id BIGINT UNSIGNED NOT NULL,
                user_id VARCHAR(64) NOT NULL,
                user_name VARCHAR(32) NOT NULL,
                avatar_color CHAR(7) NOT NULL,
                body TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_messages_room_id (room_id, id),
                CONSTRAINT fk_messages_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS rate_limits (
                fingerprint CHAR(64) NOT NULL,
                window_started BIGINT UNSIGNED NOT NULL,
                hits INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (fingerprint)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        ];
        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }
    } else {
        $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS rooms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            icon TEXT NOT NULL DEFAULT '💬',
            accent TEXT NOT NULL DEFAULT '#12a454',
            pinned TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_id INTEGER NOT NULL,
            user_id TEXT NOT NULL,
            user_name TEXT NOT NULL,
            avatar_color TEXT NOT NULL,
            body TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(room_id) REFERENCES rooms(id) ON DELETE CASCADE
        );
        CREATE INDEX IF NOT EXISTS idx_messages_room_id ON messages(room_id, id);
        CREATE TABLE IF NOT EXISTS rate_limits (
            fingerprint TEXT PRIMARY KEY,
            window_started INTEGER NOT NULL,
            hits INTEGER NOT NULL DEFAULT 0
        );
        SQL);
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn() > 0) {
        return;
    }

    $rooms = [
        ['Obrolan Umum', 'Forum diskusi bebas untuk semua anggota. Ngobrol, berbagi info, dan saling kenal. 👍', '🌇', '#bb7a72', 'Nanti malam kita bahas update forum (± pukul 21.30).'],
        ['Info Bola', 'Diskusi skor, jadwal, transfer, dan kabar sepak bola terkini.', '⚽', '#4b8e32', 'Gunakan spoiler untuk hasil pertandingan terbaru.'],
        ['Santai Malam', 'Tempat ngobrol ringan, rekomendasi film, musik, dan cerita harian.', '🏕️', '#294f70', 'Jaga suasana tetap santai dan ramah.'],
        ['Teknologi', 'Bahas gadget, software, AI, dan perkembangan teknologi.', '💻', '#3a86a8', 'Bagikan sumber saat meneruskan berita teknologi.'],
        ['Jual Beli', 'Ruang jual beli antaranggota forum dengan aman dan transparan.', '🛒', '#e7aa21', 'Dilarang bertransaksi tanpa bukti dan detail barang yang jelas.'],
        ['Bantuan Admin', 'Tanyakan aturan forum atau laporkan kendala kepada admin.', '🛡️', '#18a75c', 'Jangan membagikan data sensitif di ruang publik.'],
    ];
    $roomStmt = $pdo->prepare('INSERT INTO rooms (name, description, icon, accent, pinned) VALUES (?, ?, ?, ?, ?)');
    foreach ($rooms as $room) {
        $roomStmt->execute($room);
    }

    $seed = [
        [1, 'andi', 'Andi', '#d39a67', 'Bro, malam ini ada yang online?', '-19 minutes'],
        [1, 'me', 'Kamu', '#11a75a', 'Ada bro, lagi santai. 😊', '-17 minutes'],
        [1, 'rina', 'Rina', '#b68e9d', 'Nanti kita bahas update forum ya.', '-14 minutes'],
        [1, 'fajar', 'Fajar', '#6d8d89', 'Boleh, sekalian bahas ide fitur baru juga. Ada beberapa usulan dari anggota.', '-8 minutes'],
        [1, 'me', 'Kamu', '#11a75a', 'Oke, jam 21.30 ya?\nBiar makin banyak yang ikut. 👍', '-4 minutes'],
        [1, 'dani', 'Dani', '#658e77', 'Siap, gas! 🚀', '-1 minute'],
        [2, 'rio', 'Rio', '#568f45', 'Jadwal liga pekan ini sudah keluar.', '-34 minutes'],
        [3, 'fajar', 'Fajar', '#294f70', 'Ada rekomendasi film?', '-45 minutes'],
        [4, 'rina', 'Rina', '#3a86a8', 'Ada yang sudah coba update terbaru?', '-90 minutes'],
        [5, 'bagas', 'Bagas', '#e7aa21', 'Dijual keyboard mechanical kondisi mulus.', '-3 hours'],
        [6, 'admin', 'Admin', '#18a75c', 'Silakan baca aturan forum terlebih dahulu.', '-5 hours'],
    ];
    $messageStmt = $pdo->prepare('INSERT INTO messages (room_id, user_id, user_name, avatar_color, body, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $now = new DateTimeImmutable('now');
    foreach ($seed as $message) {
        $offset = array_pop($message);
        $message[] = $now->modify((string) $offset)->format('Y-m-d H:i:s');
        $messageStmt->execute($message);
    }
}

function forum_enforce_rate_limit(PDO $pdo): void
{
    $limit = forum_config()['send_rate_limit'];
    $now = time();
    $fingerprint = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $stmt = $pdo->prepare('SELECT window_started, hits FROM rate_limits WHERE fingerprint = ?');
    $stmt->execute([$fingerprint]);
    $current = $stmt->fetch();

    if (!$current || $now - (int) $current['window_started'] >= (int) $limit['window_seconds']) {
        $resetSql = forum_db_driver() === 'mysql'
            ? 'INSERT INTO rate_limits (fingerprint, window_started, hits) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE window_started = VALUES(window_started), hits = 1'
            : 'INSERT INTO rate_limits (fingerprint, window_started, hits) VALUES (?, ?, 1) ON CONFLICT(fingerprint) DO UPDATE SET window_started = excluded.window_started, hits = 1';
        $reset = $pdo->prepare($resetSql);
        $reset->execute([$fingerprint, $now]);
        return;
    }
    if ((int) $current['hits'] >= (int) $limit['max']) {
        forum_json(['ok' => false, 'message' => 'Terlalu banyak pesan. Tunggu sebentar lalu coba lagi.'], 429);
    }
    $increment = $pdo->prepare('UPDATE rate_limits SET hits = hits + 1 WHERE fingerprint = ?');
    $increment->execute([$fingerprint]);
}

function forum_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
