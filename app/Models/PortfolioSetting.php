<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $setting = static::find($key);
            if (! $setting) {
                return $default;
            }

            $decoded = json_decode($setting->value, true);

            return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $setting->value;
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        try {
            $formattedValue = is_array($value) ? json_encode($value) : (string) $value;

            static::updateOrCreate(
                ['key' => $key],
                ['value' => $formattedValue]
            );
        } catch (\Throwable) {
            // Gracefully ignore during unmigrated test runs
        }
    }

    /**
     * Default showcase slides for public landing page.
     *
     * @return array<int, array<string, string>>
     */
    public static function defaultSlides(): array
    {
        return [
            [
                'title' => 'Portofolio Kreatif & Desain Visual',
                'subtitle' => 'Karya Desain Grafis, UI/UX, & Identitas Visual',
                'image_url' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80',
                'description' => 'Susun tata letak halaman portofolio layaknya di Canva dengan grid rapi, tipografi presisi, dan warna elegan yang siap dipresentasikan ke klien.',
                'tag' => 'Visual Branding',
                'link' => '/p/tri',
            ],
            [
                'title' => 'Dokumen PDF Interaktif & Flipbook',
                'subtitle' => 'Koleksi CV, Resume, & Buku Karya Digital',
                'image_url' => 'https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=1200&q=80',
                'description' => 'Sematan berkas PDF langsung di dalam halaman portofolio dengan mode baca buku (flipbook) tanpa perlu mengunduh aplikasi tambahan.',
                'tag' => 'Digital Flipbook',
                'link' => '/p/tri',
            ],
            [
                'title' => 'Gantungan Kunci Fisik YOIMO NFC',
                'subtitle' => 'Kartu & Keychain Pintar Berteknologi Tap Langsung',
                'image_url' => 'https://images.unsplash.com/photo-1585060544812-6b45742d762f?auto=format&fit=crop&w=1200&q=80',
                'description' => 'Tingkatkan profesionalisme saat networking. Cukup tap gantungan kunci YOIMO ke ponsel rekan kerja untuk membuka portofolio digital seketika.',
                'tag' => 'Hardware NFC',
                'link' => '#cara-pesan',
            ],
        ];
    }

    /**
     * Default ordering steps for public landing page.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultOrderSteps(): array
    {
        return [
            [
                'step_number' => 1,
                'title' => 'Pilih Paket atau Buat Akun',
                'description' => 'Daftar akun gratis di me.myoin.id atau pilih paket Portfolio Creator Pro / Bundle YOIMO NFC untuk fitur lengkap.',
                'badge' => 'Langkah 1',
                'icon' => 'user',
            ],
            [
                'step_number' => 2,
                'title' => 'Desain Portofolio & Upload Berkas',
                'description' => 'Gunakan Canva-style page editor untuk menyusun halaman karya, menambahkan galeri foto, teks profil, dan dokumen PDF resmi.',
                'badge' => 'Langkah 2',
                'icon' => 'sparkles',
            ],
            [
                'step_number' => 3,
                'title' => 'Lakukan Pembayaran & Verifikasi Admin',
                'description' => 'Transfer ke rekening resmi LUNOU dan unggah bukti transfer (atau gratis langsung aktif saat promo bypass aktif).',
                'badge' => 'Langkah 3',
                'icon' => 'check',
            ],
            [
                'step_number' => 4,
                'title' => 'Portofolio Tayang & Terima Kartu NFC',
                'description' => 'Website portofolio Anda langsung aktif di link me.myoin.id/p/username dan kartu fisik YOIMO dikirimkan ke alamat Anda.',
                'badge' => 'Langkah 4',
                'icon' => 'card',
            ],
        ];
    }
}
