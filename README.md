# Lelang Dinamis

Katalog barang lelang dengan harga tebus tetap, dibangun dengan Laravel 12 dan MariaDB. Proyek ini memiliki katalog publik, halaman detail, pemesanan, unggah bukti pembayaran, serta panel admin untuk produk, impor CSV, pesanan, dan QRIS. Identitas situs awal **Lelang Dinamis** adalah nama sementara yang dapat diganti di panel.

## Fitur

- Produk dinamis: tambah/edit/hapus, kategori otomatis, stok, harga pasar opsional, status draft/published/sold, foto utama dan galeri.
- Impor CSV sampai 500 baris; `sku` yang sama memperbarui produk tanpa membuat duplikat. Unduh template di `/template-produk.csv`.
- Katalog dengan pencarian, filter kategori, halaman detail, dan pagination.
- Pemesanan tanpa akun pelanggan. Stok dicadangkan saat pesanan dibuat; penolakan/pembatalan mengembalikan stok.
- **Payment Section**: admin bisa mengunggah, mengganti, atau menghapus QRIS; mengubah nama merchant dan instruksi. Gambar QRIS baru langsung tampil di halaman pembayaran pesanan.
- Bukti pembayaran tersimpan di penyimpanan privat; hanya admin yang login dapat melihatnya. Admin memverifikasi secara manual.

Ini adalah alur pembelian dengan harga tebus tetap, bukan mekanisme penawaran lelang langsung atau integrasi payment gateway. QRIS belum terpasang dalam paket; unggah QRIS milik bisnis Anda dari panel setelah instalasi.

Pembeli dapat memilih Beli Sekarang atau memasukkan beberapa produk ke keranjang, lalu mengisi nama penerima, WhatsApp, alamat lengkap, dan pilihan JNE, J&T Express, Pos Indonesia, GrabExpress, atau GoSend. Setelah checkout, QRIS yang diunggah di Pengaturan tampil bersama subtotal produk. Ongkir belum dihitung otomatis dan perlu dikonfirmasi secara terpisah oleh pengelola sebelum pengiriman.

Di Pengaturan, admin dapat mengubah durasi pembayaran (default 30 menit) dan teks hitung mundur QRIS dengan `{time}`. Durasi dicatat saat pesanan dibuat; habisnya hitung mundur adalah pengingat dan tidak membatalkan pesanan atau mencegah pelanggan mengirim bukti pembayaran yang sudah dilakukan. Popup terima kasih muncul setelah bukti pembayaran berhasil diunggah. Notifikasi kecil di kiri bawah dapat ditampilkan pada semua halaman publik, halaman produk, atau halaman pesanan/checkout. Isinya berasal dari hingga 300 pesanan yang benar-benar ditandai `paid` oleh admin, ditampilkan secara acak dan berulang dengan tanggal transaksi serta tanpa nama pelanggan; jika belum ada pesanan lunas, notifikasi tidak tampil.

## Persyaratan

- PHP 8.2+ beserta ekstensi Laravel yang diperlukan, termasuk `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `xml`.
- Composer 2, MariaDB/MySQL, web server yang menunjuk ke direktori `public/`.
- HTTPS pada domain produksi.

## Pemasangan

```bash
unzip lelang-dinamis.zip
cd lelang-dinamis
composer install --no-dev --optimize-autoloader
cp .env.example .env
```

Buat database dan user MariaDB, lalu isi `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, dan `APP_URL` di `.env`. Atur `APP_ENV=production` dan `APP_DEBUG=false`. Jalankan:

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan lelang:admin
php artisan optimize
```

Atur document root domain di CyberPanel/OpenLiteSpeed ke **`/path/ke/lelang-dinamis/public`**, bukan ke root proyek. Beri hak tulis untuk user web server ke `storage/` dan `bootstrap/cache/`. Pastikan batas unggahan PHP (`upload_max_filesize`, `post_max_size`) cukup untuk foto 5 MB dan QRIS 4 MB. Buka `/admin/login` dan gunakan akun dari `lelang:admin`.

**Urutan pertama kali:** masuk admin → Pengaturan & QRIS → unggah QRIS dan isi nama merchant → Produk → buat produk dan pilih `Published`. Situs publik tetap kosong sampai produk diterbitkan.

## Format CSV

```csv
sku,nama,kategori,harga,stok,status,harga_pasar,deskripsi,kondisi
HP001,Contoh Produk,Handphone,1500000,2,draft,2500000,Deskripsi barang,Kondisi 9 dari 10
```

`sku,nama,kategori,harga,stok,status` wajib. Status yang didukung: `draft`, `published`, `sold`. Harga angka bulat tanpa simbol. Impor pertama membuat produk; impor SKU yang sama memperbarui teks/harga/stok/status, sementara foto tetap dapat diatur dari editor. Hindari menimpa `stok` secara massal ketika ada pesanan aktif; stok dalam CSV adalah stok tersedia pada saat impor.

## Impor katalog mitra

Pengelola menyatakan memiliki izin memakai katalog `pusatlelangindonesia.com` dan mitra menangani pengiriman. Importer membaca katalog publik secara bertahap, mengambil nama, kategori, harga, harga pasar, stok, deskripsi, foto utama, dan galeri (jika tersedia). Kontak, testimoni, dan QRIS sumber tidak disalin; QRIS tetap milik situs Anda. Setiap produk menyimpan URL sumber dan waktu sinkronisasi, serta ditandai sebagai barang yang dikirim mitra di halaman detail.

```bash
php artisan migrate --force
php artisan partner:import --limit=2 --skip-gallery
php artisan partner:import
php artisan partner:import --publish --skip-images --skip-gallery
```

Perintah pertama menguji dua produk sebagai draft, kedua mengimpor seluruh katalog sebagai draft dengan foto lengkap, dan terakhir menerbitkan item tersedia setelah Anda memeriksa hasilnya. Importer dapat diulang: ia mencocokkan URL sumber sehingga tidak menggandakan produk. `--skip-gallery` hanya mengambil foto utama; `--skip-images` melewatkan semua foto. Jika situs sumber tidak dapat diakses atau mengubah formatnya, importer berhenti dengan pesan error; produk yang sudah tersimpan tetap ada dan proses bisa dilanjutkan dengan perintah yang sama.

Import mitra membaca katalog tersedia dan produk sold. Gunakan `--publish` agar produk sold muncul pada menu Produk Sold. Impor ulang dengan `--skip-images --skip-gallery` memperbarui data dan status produk tanpa mengunduh gambar lagi.

Stok sumber adalah snapshot pada waktu impor, bukan sinkronisasi real-time. Pada produk yang sudah memiliki pesanan lokal, importer tidak akan menaikkan stok otomatis. Cocokkan kembali ketersediaan mitra sebelum menyetujui pesanan; bila stok mitra habis, ubah produk ke `sold` atau `draft` di panel. Foto diunduh ke penyimpanan situs, jadi pastikan kapasitas disk cukup dan sumber mengizinkan unduhan.

## Alur pesanan

1. Pelanggan memesan produk; sistem mengunci stok dalam transaksi database, mencatat harga saat dipesan, dan memberi tautan unik ke halaman pesanan.
2. Pelanggan membayar lewat QRIS yang diunggah admin, kemudian mengirim bukti bayar. Status menjadi `review`.
3. Admin membuka **Pesanan**, melihat bukti, lalu menetapkan `paid`, `rejected`, atau `cancelled`.
4. `rejected`/`cancelled` mengembalikan stok. Status akhir tidak dapat diubah lewat panel. Pesanan `pending` tidak otomatis kedaluwarsa dalam versi ini; admin dapat membatalkannya untuk melepas stok.

Jangan menganggap unggahan bukti sebagai konfirmasi uang masuk. Cocokkan transaksi dengan mutasi rekening/merchant sebelum memilih `paid`.

## Backup & pemeliharaan

Cadangkan database MariaDB dan `storage/app/public/` (foto produk serta QRIS) juga `storage/app/private/payment-proofs/` jika bukti bayar perlu disimpan. `APP_KEY` perlu disimpan aman dan konsisten. Saat update kode, jalankan `composer install --no-dev`, `php artisan migrate --force`, dan `php artisan optimize`.

## Status pengujian paket

Kode sumber disiapkan tanpa `vendor/` dan tanpa kredensial. Lingkungan pembuat paket tidak menyediakan PHP atau Composer, sehingga migrasi dan alur browser belum dapat dieksekusi di sini. Jalankan langkah instalasi di server atau mesin dengan PHP dan Composer sebelum digunakan untuk transaksi nyata.
