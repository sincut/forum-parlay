<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
$config = forum_config();
forum_db();
$cssUrl = forum_asset_url('app.css');
$jsUrl = forum_asset_url('app.js');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#08783f">
    <meta name="description" content="Forum Chat Pecinta Parlay — ruang diskusi komunitas yang cepat, ramah, dan responsif.">
    <title><?= htmlspecialchars($config['site_name']) ?> — Pecinta Parlay</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<svg class="svg-sprite" aria-hidden="true">
  <symbol id="i-chat" viewBox="0 0 24 24"><path d="M21 12a8 8 0 0 1-8 8H6l-4 2 1.3-4.4A9 9 0 1 1 21 12Z"/><path d="M8 12h8M8 8h5"/></symbol>
  <symbol id="i-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></symbol>
  <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></symbol>
  <symbol id="i-edit" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></symbol>
  <symbol id="i-bookmark" viewBox="0 0 24 24"><path d="M6 3h12v18l-6-4-6 4Z"/></symbol>
  <symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3V2.8h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/></symbol>
  <symbol id="i-pin" viewBox="0 0 24 24"><path d="m12 17-5 5M15 3l6 6-4 1-4 6-5-5 6-4Z"/></symbol>
  <symbol id="i-phone" viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2.1Z"/></symbol>
  <symbol id="i-video" viewBox="0 0 24 24"><rect x="2" y="5" width="15" height="14" rx="2"/><path d="m17 10 5-3v10l-5-3Z"/></symbol>
  <symbol id="i-back" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></symbol>
  <symbol id="i-close" viewBox="0 0 24 24"><path d="m18 6-12 12M6 6l12 12"/></symbol>
  <symbol id="i-paperclip" viewBox="0 0 24 24"><path d="m21.4 11.6-8.5 8.5a6 6 0 0 1-8.5-8.5l9-9a4 4 0 0 1 5.7 5.7l-9 9a2 2 0 1 1-2.8-2.8l8.4-8.4"/></symbol>
  <symbol id="i-send" viewBox="0 0 24 24"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></symbol>
  <symbol id="i-smile" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></symbol>
  <symbol id="i-mic" viewBox="0 0 24 24"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v5M8 22h8"/></symbol>
  <symbol id="i-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></symbol>
  <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 22a8 8 0 0 1 16 0"/></symbol>
  <symbol id="i-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2Z"/></symbol>
</svg>

<main class="app-shell" id="app">
    <aside class="rail" aria-label="Navigasi utama">
        <div class="brand-mark"><svg><use href="#i-chat"/></svg></div>
        <nav>
            <button class="rail-button active" aria-label="Forum"><svg><use href="#i-chat"/></svg></button>
            <button class="rail-button" aria-label="Anggota"><svg><use href="#i-users"/></svg></button>
            <button class="rail-button" aria-label="Tersimpan"><svg><use href="#i-bookmark"/></svg></button>
            <button class="rail-button" aria-label="Pengaturan"><svg><use href="#i-settings"/></svg></button>
        </nav>
        <button class="mini-avatar profile-button" aria-label="Ubah profil"><span>KA</span><i></i></button>
    </aside>

    <section class="room-panel" aria-label="Daftar forum">
        <header class="room-panel-header">
            <div class="wordmark"><span class="brand-mark small"><svg><use href="#i-users"/></svg></span><strong>Forum Chat</strong></div>
            <button class="soft-button" id="new-room-button" aria-label="Buat ruang"><svg><use href="#i-edit"/></svg></button>
        </header>
        <label class="search-box"><svg><use href="#i-search"/></svg><input id="room-search" type="search" placeholder="Cari forum atau pesan..." autocomplete="off"></label>
        <div class="rooms" id="rooms" aria-live="polite"><div class="loading-list">Memuat forum…</div></div>
        <nav class="mobile-tabs" aria-label="Navigasi seluler">
            <button class="active"><svg><use href="#i-home"/></svg><span>Forum</span></button>
            <button><svg><use href="#i-chat"/></svg><span>Chat</span></button>
            <button><svg><use href="#i-bell"/></svg><span>Notifikasi</span><i></i></button>
            <button class="profile-button"><svg><use href="#i-user"/></svg><span>Profil</span></button>
        </nav>
    </section>

    <section class="chat-panel" aria-label="Percakapan aktif">
        <header class="chat-header">
            <button class="icon-button back-button" id="back-button" aria-label="Kembali"><svg><use href="#i-back"/></svg></button>
            <div class="room-avatar hero" id="chat-avatar">🌇</div>
            <div class="chat-title"><strong id="chat-room-name">Obrolan Umum</strong><span>128 anggota · <i></i> 18 online</span></div>
            <div class="chat-actions">
                <button class="icon-button desktop-pin" aria-label="Sematkan"><svg><use href="#i-pin"/></svg></button>
                <button class="icon-button" id="message-search-button" aria-label="Cari pesan"><svg><use href="#i-search"/></svg></button>
                <button class="icon-button wide-only" aria-label="Panggilan suara"><svg><use href="#i-phone"/></svg></button>
                <button class="icon-button wide-only" aria-label="Panggilan video"><svg><use href="#i-video"/></svg></button>
                <button class="more-button" id="detail-toggle" aria-label="Detail grup">⋮</button>
            </div>
        </header>
        <div class="pinned" id="pinned"><svg><use href="#i-pin"/></svg><div><strong>Pesan Disematkan</strong><span id="pinned-text">Memuat…</span></div><button id="hide-pin" aria-label="Tutup"><svg><use href="#i-close"/></svg></button></div>
        <label class="message-search" id="message-search"><svg><use href="#i-search"/></svg><input type="search" placeholder="Cari dalam percakapan…"><button type="button" aria-label="Tutup"><svg><use href="#i-close"/></svg></button></label>
        <div class="messages" id="messages" aria-live="polite" aria-busy="true">
            <div class="day-pill">Hari ini</div>
            <div class="loading-messages">Memuat percakapan…</div>
        </div>
        <form class="composer" id="message-form">
            <button type="button" class="composer-icon" id="emoji-button" aria-label="Emoji"><svg><use href="#i-smile"/></svg></button>
            <button type="button" class="composer-icon" aria-label="Lampiran"><svg><use href="#i-paperclip"/></svg></button>
            <textarea id="message-input" rows="1" maxlength="1200" placeholder="Ketik pesan di Obrolan Umum…" aria-label="Pesan"></textarea>
            <button type="button" class="composer-icon mic" aria-label="Pesan suara"><svg><use href="#i-mic"/></svg></button>
            <button type="submit" class="send-button" aria-label="Kirim pesan"><svg><use href="#i-send"/></svg></button>
        </form>
        <div class="emoji-picker" id="emoji-picker" aria-label="Pilih emoji"></div>
    </section>

    <aside class="details-panel" id="details-panel" aria-label="Detail grup">
        <header><strong>Detail Grup</strong><button class="icon-button" id="detail-close" aria-label="Tutup detail"><svg><use href="#i-close"/></svg></button></header>
        <section class="group-summary"><div class="room-avatar detail" id="detail-avatar">🌇</div><div><strong id="detail-name">Obrolan Umum</strong><span>128 anggota · <i></i> 18 online</span></div></section>
        <p id="detail-description">Forum diskusi bebas untuk semua anggota.</p>
        <section class="detail-section"><div class="section-heading"><strong>Anggota (128)</strong><button>Lihat semua</button></div><div class="member-row"><span class="member c1">A</span><span class="member c2">R</span><span class="member c3">F</span><span class="member c4">D</span><span class="member more">+124</span></div></section>
        <section class="detail-section"><div class="section-heading"><strong>Media Bersama</strong><button>Lihat semua</button></div><div class="media-grid"><span>🏔️</span><span>💻</span><span>⚽</span><span>🌆</span></div></section>
        <section class="rules"><div class="rules-title"><span>▤</span><strong>Aturan Forum</strong></div><ol><li>Gunakan bahasa yang sopan dan saling menghargai.</li><li>Dilarang spam, promosi berlebihan, atau konten SARA.</li><li>Jaga topik tetap relevan dengan diskusi.</li><li>Ikuti arahan dari admin.</li></ol></section>
    </aside>
</main>

<dialog id="profile-dialog">
    <form method="dialog" id="profile-form"><div class="dialog-head"><strong>Profil kamu</strong><button value="cancel" aria-label="Tutup">×</button></div><p>Nama ini terlihat oleh anggota forum lain.</p><label>Nama tampilan<input id="profile-name" maxlength="32" required></label><menu><button value="cancel" class="cancel">Batal</button><button value="default" class="save">Simpan</button></menu></form>
</dialog>
<div class="toast" id="toast" role="status"></div>
<script>window.FORUM_CONFIG = <?= json_encode(['api' => 'api.php'], JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= htmlspecialchars($jsUrl, ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>