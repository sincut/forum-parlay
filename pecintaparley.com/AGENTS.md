# Aturan kerja Codex — Pecinta Parlay

Instruksi ini berlaku untuk seluruh repository dan setiap prompt update berikutnya.

1. Sebelum mengubah apa pun, baca `pecintaparley.com/README.md`, file ini, dan file yang terlibat dalam perubahan. Petakan dahulu subdomain yang terdampak.
2. Periksa katalog skill Codex yang tersedia pada sesi tersebut. Gunakan skill yang benar-benar tersedia dan relevan sebelum bekerja; jangan mengklaim atau mengandalkan skill yang tidak tersedia. Untuk perubahan UI gunakan skill website/UI yang tersedia; untuk dokumen atau skill khusus gunakan skill yang sesuai bila tersedia.
3. Jaga arsitektur upload-manual: PHP 8+, PDO MySQL (dengan SQLite hanya sebagai fallback lokal), HTML/CSS/JavaScript tanpa dependency build wajib. Jangan menambah framework, CDN, atau layanan pihak ketiga tanpa persetujuan eksplisit.
4. Tugas setiap folder tidak boleh tertukar:
   - `pecintaparley.com/`: home publik yang indexable sekaligus lokasi core PHP, konfigurasi MySQL, sumber asset forum, dan fallback SQLite lokal; file internalnya wajib diblokir dari akses web.
   - `img.pecintaparley.com/`: asset publik saja; jangan taruh rahasia atau database di sini.
   - `forum.pecintaparley.com/`: document root forum.
   - `admin.pecintaparley.com/`: document root admin; selalu `noindex` dan wajib login.
   - root project: folder induk seluruh document root; tidak untuk dijadikan document root publik.
5. Semua asset baru untuk home, forum, atau admin harus disajikan dari `img.pecintaparley.com` di production. Perbarui versi URL asset saat konten asset berubah agar cache tidak menahan versi lama.
6. Forum harus responsif pada desktop dan mobile. Jangan merusak alur daftar ruang → buka chat → kirim/poll pesan → kembali ke daftar pada mobile.
7. Admin harus membaca hash password dari `admin.pecintaparley.com/.env`; jangan pernah hard-code password, menulis secret ke README, atau memasukkan `.env` ke Git.
8. Saat mengubah `README.md`, cocokkan ulang setiap klaimnya dengan implementasi aktual. Lalu jalankan ulang seluruh pemeriksaan pada poin 9; dokumentasi tidak boleh menjanjikan fitur yang belum bekerja.
9. Setelah perubahan, minimal jalankan:
   - `php -l` untuk semua file PHP;
   - `node --check pecintaparley.com/assets/app.js` jika file JavaScript forum berubah;
   - smoke test `bootstrap`, baca pesan, dan kirim pesan API;
   - login admin (bila konfigurasi hash tersedia);
   - cek `robots.txt`, `sitemap.xml`, dan metadata canonical home bila home/SEO berubah;
   - cek layout forum pada breakpoint mobile dan desktop bila UI berubah.
10. Jangan commit database runtime (`pecintaparley.com/data/*.sqlite*`), `.env`, log, cookie/session, credential, maupun data anggota. Jangan menghapus perubahan pengguna yang tidak berkaitan.
11. Sebelum menyatakan production-ready, cek ulang permission folder `data`, `.htaccess`, zona waktu, asset origin, HTTPS, error log, endpoint API, akses admin, serta bahwa home dan forum memakai document root yang tepat.
