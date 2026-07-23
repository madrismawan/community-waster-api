# Community Waste Management API

REST API untuk mengelola data rumah tangga, limbah dan pengambilan sampah, pembayaran, serta report. Aplikasi dibangun menggunakan Laravel, MongoDB, JWT Authentication, dan Repository Pattern untuk memisahkan akses data dari business logic.

## Prasyarat

Pastikan perangkat sudah memiliki:

- Git
- Docker Engine atau Docker Desktop
- Docker Compose v2

PHP, Composer, MongoDB, Nginx, dan dependency aplikasi dijalankan melalui container sehingga tidak perlu dipasang langsung di host.

## Menjalankan Aplikasi

Salin environment file:

```bash
cp .env.example .env
```

Build dan jalankan container:

```bash
docker compose up --build -d
```

Periksa container:

```bash
docker compose ps
```

Buat JWT secret:

```bash
docker compose exec app php artisan jwt:secret --force
```

Restart container aplikasi agar PHP-FPM memuat JWT secret terbaru:

```bash
docker compose restart app
```

Aplikasi tersedia di:

```text
http://localhost:8000
```

Port dapat diubah melalui `APP_PORT` di `.env`.

## Migration dan Seeder

Masuk ke container aplikasi:

```bash
docker compose exec app sh
```

Jalankan migration:

```bash
php artisan migrate
```

Jalankan seluruh seeder:

```bash
php artisan db:seed
```

Atau jalankan migration dan seeder sekaligus:

```bash
php artisan migrate --seed
```

Keluar dari container:

```bash
exit
```

Seeder menyediakan akun:

```text
Email    : admin@example.com
Password : password
```

## Format Response

```json
{
  "success": true,
  "message": "Request completed successfully.",
  "data": {},
  "meta": {},
  "errors": null
}
```

## Persiapan API

```text
BASE_URL=http://localhost:8000/api
TOKEN=<access_token_dari_login>
```

Gunakan token pada endpoint terproteksi:

```http
Authorization: Bearer $TOKEN
```

## Import Postman Collection

Collection Postman tersedia pada file [Inosoft.postman_collection.json](./Inosoft.postman_collection.json).

Cara mengimpornya:

1. Buka aplikasi Postman.
2. Klik **Import**.
3. Pilih **Files**, lalu pilih file `Inosoft.postman_collection.json` dari root project.
4. Buka collection **Inosoft**, lalu masuk ke tab **Variables**.
5. Isi variable `base_url` dengan:

```text
http://localhost:8000/api
```

6. Jalankan request **Auth > Login** menggunakan akun hasil seeder:

```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

Request login pada collection akan menyimpan `access_token` secara otomatis ke variable `token`. Request pickup dan payment akan menggunakan token tersebut melalui Bearer Authentication.

## Authentication

### Register

```http
POST $BASE_URL/auth/register
```

Payload:

```json
{
  "name": "Admin Waste",
  "email": "admin.waste@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### Login

```http
POST $BASE_URL/auth/login
```

Payload:

```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

Simpan nilai `data.access_token` dari response sebagai `TOKEN`.

### Authenticated User

```http
GET $BASE_URL/auth/me
```

Payload: tidak ada.

### Logout

```http
POST $BASE_URL/auth/logout
```

Payload: tidak ada.

## Households

### List Households

```http
GET $BASE_URL/households?search=Made&block=B&no=01&per_page=15&page=1
```

Payload: tidak ada.

Query yang tersedia:

```text
search, block, no, per_page, page
```

### Create Household

```http
POST $BASE_URL/households
```

Payload:

```json
{
  "owner_name": "Made Rismawan",
  "address": "Jalan Kenanga No. 3",
  "block": "B",
  "no": "01"
}
```

### Show Household

```http
GET $BASE_URL/households/{id}
```

Payload: tidak ada.

### Update Household

```http
PUT $BASE_URL/households/{id}
```

Payload:

```json
{
  "owner_name": "Made Rismawan Updated",
  "address": "Jalan Kenanga No. 5",
  "block": "C",
  "no": "02"
}
```

### Soft Delete Household

```http
DELETE $BASE_URL/households/{id}
```

Payload: tidak ada.

### Restore Household

```http
PUT $BASE_URL/households/{id}/restore
```

Payload: tidak ada.

## Pickups

Seluruh endpoint pickup membutuhkan JWT.

Waste type:

```text
organic, plastic, paper, electronic
```

Waste status:

```text
pending, scheduled, completed, canceled
```

### List Pickups

```http
GET $BASE_URL/pickups?household_id={household_id}&type=organic&status=pending&per_page=15&page=1
```

Payload: tidak ada.

Query yang tersedia:

```text
household_id, type, status, per_page, page
```

### Create Pickup

```http
POST $BASE_URL/pickups
```

Payload:

```json
{
  "household_id": "64f123456789abcdef123456",
  "type": "organic"
}
```

Pickup baru memiliki status `pending`. Pickup tidak dapat dibuat jika household masih mempunyai payment berstatus `pending`.

### Schedule Pickup

```http
PUT $BASE_URL/pickups/{id}/schedule
```

Payload:

```json
{
  "pickup_date": "2026-08-01 09:00:00"
}
```

Untuk electronic waste:

```json
{
  "pickup_date": "2026-08-01 09:00:00",
  "safety_check": true
}
```

### Complete Pickup

```http
PUT $BASE_URL/pickups/{id}/complete
```

Payload: tidak ada.

Proses ini mengubah status pickup menjadi `completed` dan membuat payment berstatus `pending` dengan tanggal pembayaran satu minggu dari waktu completion.

### Cancel Pickup

```http
PUT $BASE_URL/pickups/{id}/cancel
```

Payload: tidak ada.

## Payments

Seluruh endpoint payment membutuhkan JWT.

Payment status:

```text
pending, paid, failed
```

### List Payments

```http
GET $BASE_URL/payments?household_id={household_id}&status=pending&start_date=2026-07-01&end_date=2026-07-31&per_page=15&page=1
```

Payload: tidak ada.

Query yang tersedia:

```text
household_id, status, start_date, end_date, per_page, page
```

`start_date` dan `end_date` digunakan bersama untuk memfilter rentang `payment_date`.

### Create Payment

```http
POST $BASE_URL/payments
```

Payload:

```json
{
  "household_id": "64f123456789abcdef123456",
  "amount": 50000
}
```

Payment baru memiliki status `pending`. `payment_date` otomatis diatur satu minggu dari waktu pembuatan.

### Confirm Payment

```http
PUT $BASE_URL/payments/{id}/confirm
```

Payload: tidak ada.

Setelah berhasil dikonfirmasi, status payment berubah menjadi `paid`.

## Reports

### Waste Summary

```http
GET $BASE_URL/reports/waste-summary
```

Payload: tidak ada.

Mengembalikan jumlah pickup yang dikelompokkan berdasarkan waste type dan status.

### Payment Summary

```http
GET $BASE_URL/reports/payment-summary
```

Payload: tidak ada.

Mengembalikan jumlah dan total payment per status, serta total revenue dari payment berstatus `paid`.

### Household Pickup and Payment History

```http
GET $BASE_URL/reports/households/{id}/history
```

Payload: tidak ada.

Mengembalikan informasi household beserta riwayat pickup dan payment.

## Perintah Docker Berguna

Melihat log aplikasi:

```bash
docker compose logs -f app
```

Melihat log MongoDB:

```bash
docker compose logs -f mongodb
```

Menghentikan container:

```bash
docker compose down
```

Menghentikan container sekaligus menghapus volume database:

```bash
docker compose down -v
```

Perintah terakhir akan menghapus seluruh data MongoDB lokal secara permanen.
