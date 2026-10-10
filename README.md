# LUNOU — Social Identity Platform & Custom NFC Keychains

> **Domain Resmi**: [id.lunou.tech](https://id.lunou.tech)  
> **Tagline**: *More Than a Link. A Little Piece of You.*

LUNOU adalah platform identitas digital personal yang menghubungkan seluruh media sosial, portofolio, dan cerita Anda ke dalam satu profil yang elegan, serta terintegrasi langsung dengan produk fisik gantungan kunci custom berchip NFC pintar.

---

## 🌟 Fitur Utama

- **Profile-First Digital Space**: Kustomisasi profil lengkap dengan katalog ikon sosial media, biografi, tema warna, dan tautan publik responsif (`id.lunou.tech/{username}`).
- **Gantungan Kunci Pintar NFC**: Produk fisik gantungan kunci custom berkarakter maskot LUNOU dengan chip RFID/NFC berkecepatan tinggi.
- **Sistem Pemesanan & Pembayaran**:
  - Pilihan karakter maskot (#1 - #10) atau upload gambar desain kustom.
  - Pembayaran transfer rekening **blu by BCA Digital** (`007377724613` a/n **Tri febriansah**).
  - Harga promo spesial **Rp 49.000 (Lifetime Access)** dari harga normal **Rp 100.000**.
  - Konfirmasi otomatis via WhatsApp (`085862319524`) dan pelacakan status pesanan (*Tinggal Pantau*).
- **Fitur Redeem Pesanan**: Memungkinkan pembeli dari luar web / WhatsApp untuk mengaktifkan dan menghubungkan gantungan kunci ke profil secara instan via Kode Order.
- **Super Admin Console**:
  - Manajemen akun pengguna & verifikasi pembayaran order pending.
  - Pembuatan order offline / pemesanan luar web.
  - Manajemen chip NFC hardware (unassigned & bound devices).
- **Otentikasi & Keamanan**: Google OAuth (Socialite), Email/Password, CSRF protection, dan Audit Logging.

---

## 🛠️ Tech Stack

- **Backend**: Laravel 13, PHP 8.4
- **Frontend**: Vue 3 SPA, Inertia.js v2, Tailwind CSS
- **Database**: MySQL
- **Build Tool**: Vite

---

## 🚀 Panduan Instalasi Lokal

```bash
# 1. Clone repositori
git clone https://github.com/Trifebri3/idlunouuu.git
cd idlunouuu

# 2. Install dependensi PHP & Node.js
composer install
npm install

# 3. Konfigurasi Environment (.env)
cp .env.example .env
php artisan key:generate

# 4. Migrasi & Seeder Database
php artisan migrate --seed

# 5. Jalankan server lokal
php artisan serve
npm run dev
```

---

## 📜 Lisensi & Hak Cipta

&copy; 2026 **LUNOU** (`id.lunou.tech`). All rights reserved.
