# Instalasi POSPIN

Aplikasi ini web (Laravel + Vue). Dipakai di laptop, tablet, atau HP lewat browser. Tidak perlu APK Play Store.

## Yang harus terpasang

- PHP 8.3 atau lebih baru (disarankan 8.5)
- Ekstensi PHP: `bcmath`, `ctype`, `curl`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`
- Composer
- Node.js 20 atau lebih baru + npm
- MySQL 8 (disarankan untuk toko)

Cek cepat:

```sh
php -v
composer -V
node -v
mysql --version
```

## 1. Siapkan folder project

Extract ZIP source, lalu masuk ke foldernya:

```sh
cd pos-system
```

## 2. Buat database

Di MySQL:

```sql
CREATE DATABASE pos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 3. Salin pengaturan

```sh
cp .env.example .env
php artisan key:generate
```

Edit file `.env`:

```env
APP_NAME="POSPIN"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_db
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=log
```

Isi `DB_PASSWORD` sesuai MySQL kamu. `APP_URL` harus sama dengan alamat yang dibuka di browser.

## 4. Install dependency dan database

```sh
composer install
npm install
php artisan migrate
php artisan db:seed
php artisan storage:link
npm run build
```

`db:seed` mengisi toko demo, produk contoh, dan akun login. Jangan dijalankan di toko yang sudah ada datanya.

## 5. Jalankan aplikasi

**Mode development** (server + Vite sekaligus):

```sh
composer run dev
```

Atau terpisah:

```sh
php artisan serve
```

Buka [http://localhost:8000](http://localhost:8000).

Kalau tampilan CSS/JS belum berubah, pastikan `npm run build` atau `npm run dev` sudah jalan.

## 6. Login demo

Semua password akun seed: `password`

| Peran | Alamat | Email / PIN |
| --- | --- | --- |
| Portal | `/` | pilih Admin atau Kasir |
| Owner | `/login` | `owner@pos.test` |
| Admin | `/login` | `admin@pos.test` |
| Kasir | `/kasir/login` | PIN `123456` atau kartu `EMP-001` |

Ganti password dan PIN sebelum dipakai toko sungguhan.

Kasir harus **buka shift** dulu baru bisa transaksi. Isi modal awal kas, lalu masuk ke `/pos`.

## Tablet atau HP di toko

1. Laptop/server dan tablet harus satu Wi-Fi.
2. Cari IP laptop, contoh `192.168.1.10`.
3. Di `.env` set `APP_URL=http://192.168.1.10:8000`
4. Jalankan:

```sh
php artisan serve --host=0.0.0.0 --port=8000
```

5. Di tablet buka `http://192.168.1.10:8000`, login kasir, lalu **Add to Home Screen** di Chrome.

Untuk toko sungguhan, pasang di server HTTPS (bukan `artisan serve`).

## Setelah install, sebelum buka kasir

1. Pengaturan toko: nama, alamat, telepon, footer struk
2. Ganti akun demo / matikan `APP_DEBUG` di production
3. Import atau input produk + stok
4. Tempel QRIS merchant di meja (pembayaran QRIS/kartu/e-wallet masih konfirmasi manual + catatan)
5. Tes print struk dari browser
6. Backup database setiap hari

## Production singkat

```sh
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set di `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kasir.tokokamu.com
```

Folder `storage/` dan `bootstrap/cache/` harus bisa ditulis web server. Document root mengarah ke folder `public/`.

## Masalah yang sering muncul

| Gejala | Perbaikan |
| --- | --- |
| Halaman putih / CSS rusak | Jalankan `npm run build` |
| `SQLSTATE` / table not found | `php artisan migrate` lalu `php artisan db:seed` (kalau database masih kosong) |
| Logo/foto tidak muncul | `php artisan storage:link` |
| Kasir ditolak masuk POS | Buka shift dulu di `/shifts/open` |
| ViteException manifest | `npm run build` atau `npm run dev` |
| Tablet tidak bisa buka | Pakai `--host=0.0.0.0` dan samakan `APP_URL` dengan IP LAN |

## Perintah berguna

```sh
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan test --compact
```
