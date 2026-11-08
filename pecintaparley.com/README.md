# Pecinta Parlay — paket multi-subdomain

Repository ini adalah paket siap upload manual untuk ekosistem Pecinta Parlay. Tidak membutuhkan npm, Composer, atau proses build. Forum memakai PHP 8 dan PDO MySQL di production, dengan SQLite sebagai fallback lokal. Setiap domain memiliki document root sendiri dan berbagi core yang berada di document root domain utama `pecintaparley.com/`.

| Folder document root | Domain | Tugas |
| --- | --- | --- |
| `pecintaparley.com/` | `https://pecintaparley.com` | Homepage publik, SEO, serta core PHP/config bersama. |
| `img.pecintaparley.com/` | `https://img.pecintaparley.com` | Host semua asset publik untuk home, forum, dan admin. |
| `forum.pecintaparley.com/` | `https://forum.pecintaparley.com` | Forum chat responsif. |
| `admin.pecintaparley.com/` | `https://admin.pecintaparley.com` | Panel moderasi yang dilindungi password. |

Root induk hanya memuat empat folder domain. Sumber asset forum berada di `pecintaparley.com/assets/`; `pecintaparley.com/data/` menyimpan fallback SQLite lokal. Keduanya ditolak dari akses web langsung melalui `.htaccess`. Homepage utama tetap dilayani oleh `pecintaparley.com/index.html`; `index.php` di folder yang sama adalah core tampilan forum yang dipanggil oleh wrapper subdomain forum.

## Forum Chat

Pada desktop tersedia sidebar, daftar ruang, percakapan, dan detail grup. Pada mobile, daftar ruang menjadi layar awal lalu chat terbuka penuh. Fitur yang tersedia:

- Enam ruang awal dengan deskripsi dan pinned message.
- Percakapan multi-user di MySQL, polling setiap tiga detik, profil tamu per browser, dan rate limit kirim pesan.
- Pencarian forum/pesan, emoji picker, panel detail grup, pengiriman Enter, dan multiline Shift+Enter.
- Panel admin untuk mengubah nama/deskripsi/pinned message ruang serta menghapus 20 pesan terakhir.
- Layout responsif tanpa font, framework, atau script pihak ketiga.

## Struktur integrasi

- `forum.pecintaparley.com/index.php` memanggil `pecintaparley.com/index.php`.
- `forum.pecintaparley.com/api.php` memanggil `pecintaparley.com/api.php`.
- `admin.pecintaparley.com/index.php` memakai `pecintaparley.com/db.php`.
- `pecintaparley.com/config.php` membaca koneksi MySQL dari `.env`; SQLite dapat dipilih khusus untuk development lokal.
- `img.pecintaparley.com/forum-assets.php` menyajikan `pecintaparley.com/assets/app.css` dan `pecintaparley.com/assets/app.js`.
- `pecintaparley.com/.htaccess` mempertahankan `index.html` sebagai halaman utama dan menolak akses web ke `.env`, konfigurasi, dokumentasi, serta file internal lain.

Struktur ini mengandalkan Apache yang mengizinkan PHP subdomain asset membaca folder `pecintaparley.com/assets/`. Jika hosting memakai `open_basedir`, masukkan folder induk paket ini ke daftar path yang diperbolehkan.

## Deploy production manual

1. Upload seluruh folder repository ke satu folder induk akun hosting, misalnya `/home/USER/pecinta-parlay`. Jangan arahkan domain ke folder induk tersebut.
2. Atur document root berikut:

   | Domain | Document root |
   | --- | --- |
   | `pecintaparley.com` | `/home/USER/pecinta-parlay/pecintaparley.com` |
   | `img.pecintaparley.com` | `/home/USER/pecinta-parlay/img.pecintaparley.com` |
   | `forum.pecintaparley.com` | `/home/USER/pecinta-parlay/forum.pecintaparley.com` |
   | `admin.pecintaparley.com` | `/home/USER/pecinta-parlay/admin.pecintaparley.com` |

3. Buat database dan user MySQL dari panel hosting, berikan user tersebut seluruh privilege pada database forum, lalu pastikan ekstensi PHP `pdo_mysql` aktif.
4. Salin `pecintaparley.com/.env.example` menjadi `pecintaparley.com/.env`, lalu isi seluruh placeholder berikut:

   ```ini
   ASSET_ORIGIN=https://img.pecintaparley.com
   DB_DRIVER=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=nama_database
   DB_USER=user_database
   DB_PASSWORD="password_database"
   DB_CHARSET=utf8mb4
   ```

5. Salin `admin.pecintaparley.com/.env.example` menjadi `admin.pecintaparley.com/.env`. Buat hash password:

   ```powershell
   php -r "echo password_hash('GANTI_PASSWORD_KUAT', PASSWORD_DEFAULT), PHP_EOL;"
   ```

   Taruh hasilnya pada `ADMIN_PASSWORD_HASH=`. Jangan memakai password plain text dan jangan memasukkan `.env` ke Git.

6. Aktifkan HTTPS untuk semua domain. Pastikan Apache membaca `.htaccess`. Uji langsung bahwa `https://pecintaparley.com/.env`, `/config.php`, `/db.php`, `/README.md`, `/AGENTS.md`, `/.run`, dan `/data/forum.sqlite` mengembalikan 403/404.
7. Buka forum. Tabel MySQL dan data contoh dibuat otomatis saat database masih kosong. Uji bootstrap, baca pesan, kirim pesan, dan login admin sebelum diumumkan.

File `pecintaparley.com/data/forum.sqlite` lama dipertahankan sebagai cadangan lokal dan tidak dipakai ketika `DB_DRIVER=mysql`. Data SQLite tidak otomatis disalin ke MySQL.

## Google indexing homepage

Homepage memiliki title/description, canonical URL, Open Graph, Schema.org `WebSite`, `robots.txt`, dan `sitemap.xml`. Google tetap menentukan waktu serta hasil indeks. Setelah live, verifikasi `pecintaparley.com` di Google Search Console lalu submit `https://pecintaparley.com/sitemap.xml`. Forum dan admin bukan target SEO.

## Konfigurasi

`pecintaparley.com/config.php` menyimpan zona waktu `Asia/Jakarta`, interval polling 3000 ms, batas 1200 karakter per pesan, dan rate limit 8 pesan per 20 detik per IP. Koneksi database hanya dibaca dari `pecintaparley.com/.env`: `DB_DRIVER`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, dan `DB_CHARSET`.

Admin tidak memiliki password default. Jika `ADMIN_PASSWORD_HASH` kosong, login ditolak. Login memakai session cookie `HttpOnly`/`SameSite=Strict`, CSRF token untuk aksi admin, dan pembatasan percobaan login sederhana.

## Menjalankan lokal

Untuk memakai fallback SQLite saat development tanpa mengubah `.env`, jalankan server forum dari root induk repository:

```powershell
$env:DB_DRIVER = 'sqlite'
php -S 127.0.0.1:8080 -t forum.pecintaparley.com
```

Buka `http://127.0.0.1:8080`. Jalankan homepage secara terpisah bila diperlukan:

```powershell
php -S 127.0.0.1:8081 -t pecintaparley.com
```

Built-in PHP server hanya untuk development. Asset forum production tetap berasal dari nilai `ASSET_ORIGIN`.

## Pemeriksaan sebelum deploy

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
node --check pecintaparley.com/assets/app.js
```

Lanjutkan dengan smoke test `api.php?action=bootstrap`, pembacaan pesan, dan pengiriman pesan. Uji homepage, `robots.txt`, `sitemap.xml`, canonical, login admin, serta layout forum pada viewport desktop dan mobile.

## Aturan update Codex

`pecintaparley.com/AGENTS.md` adalah kontrak kerja update selanjutnya. Setiap perubahan wajib memeriksa skill yang tersedia dan relevan, membaca dokumentasi/implementasi terkait, menjalankan pengujian ulang, dan mengaudit README bila dokumentasi diubah.
