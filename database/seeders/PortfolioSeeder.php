<?php

namespace Database\Seeders;

use App\Models\Portfolio;
use App\Models\PortfolioEntitlement;
use App\Models\PortfolioPage;
use App\Models\PortfolioPaymentMethod;
use App\Models\PortfolioPlan;
use App\Models\PortfolioSetting;
use App\Models\PortfolioVoucher;
use App\Models\User;
use Illuminate\Database\Seeder;

class PortfolioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Subscription Plan
        $plan = PortfolioPlan::updateOrCreate(
            ['slug' => 'creator-pro'],
            [
                'name' => 'LUNOU Portfolio Creator Pro',
                'description' => 'Akses penuh Web Portfolio Builder visual ala Canva, hosting publik di /p/username, mode buku digital (flipbook), viewer PDF interaktif, galeri proyek, dan integrasi profil sosial.',
                'price' => 99000,
                'promo_price' => 89100,
                'is_promo_active' => true,
                'promo_ends_at' => now()->addDays(30),
                'duration_days' => 30,
                'max_pdf_files' => 10,
                'max_pdf_size_mb' => 5,
                'features' => [
                    'Visual Page-based Canvas Editor ala Canva (Mudah & Tanpa Koding)',
                    'Publikasi Website Responsif di me.myoin.id/p/namapengguna',
                    'Viewer PDF Tertanam & Mode Buku Digital (Flipbook)',
                    'Kapasitas hingga 10 File PDF Portofolio (Maks. 5 MB / file)',
                    'Showcase Proyek, Galeri Foto & Desain Tanpa Batas',
                    'Popup & Teaser Otomatis di Halaman Profil me.myoin.id',
                    'Pengaturan Hak Download & Tautan Eksternal Behance/GitHub',
                    'Performa Cepat & Responsif di Layar HP, Tablet, dan Desktop',
                ],
                'is_active' => true,
            ]
        );

        $bundlePlan = PortfolioPlan::updateOrCreate(
            ['slug' => 'bundle-pro-nfc'],
            [
                'name' => 'Bundle All-in-One: Portofolio Pro + Keychain NFC',
                'description' => 'Akses penuh Web Portfolio Builder (30 Hari) PLUS 1 Buah Gantungan Kunci Fisik YOIMO NFC Custom (Chip NTAG216 Tahan Air) dikirim langsung ke alamat Anda.',
                'price' => 169000,
                'promo_price' => 149000,
                'is_promo_active' => true,
                'promo_ends_at' => now()->addDays(30),
                'duration_days' => 30,
                'max_pdf_files' => 10,
                'max_pdf_size_mb' => 5,
                'features' => [
                    'Semua fitur Web Portfolio Digital Pro (30 Hari Akses)',
                    '1 Buah Gantungan Kunci Fisik YOIMO NFC Custom (Chip NTAG216 Tahan Air)',
                    'Bebas Pilih Desain / Karakter Maskot & Grafir Nama Personal',
                    'Gratis Konsultasi Pemrograman URL & Pengiriman Aman',
                    'Tap Langsung ke Ponsel Tanpa Perlu Aplikasi Tambahan',
                ],
                'is_active' => true,
            ]
        );

        // 2. Vouchers
        PortfolioVoucher::updateOrCreate(
            ['code' => 'LUNOU5'],
            [
                'discount_type' => 'percent',
                'discount_amount' => 10,
                'max_discount' => 15000,
                'quota' => 100,
                'used_count' => 0,
                'expires_at' => now()->addDays(60),
                'is_active' => true,
            ]
        );

        PortfolioVoucher::updateOrCreate(
            ['code' => 'KARYABARU'],
            [
                'discount_type' => 'fixed',
                'discount_amount' => 20000,
                'quota' => 50,
                'used_count' => 0,
                'expires_at' => now()->addDays(45),
                'is_active' => true,
            ]
        );

        // 3. Bank Accounts (Encrypted automatically in DB via Eloquent Casts)
        PortfolioPaymentMethod::updateOrCreate(
            ['bank_name' => 'BCA (Bank Central Asia)'],
            [
                'account_number' => '8830192841',
                'account_holder' => 'PT LUNOU TEKNOLOGI KREATIF',
                'instructions' => 'Transfer tepat sesuai nominal tagihan. Simpan bukti transfer untuk diunggah di formulir konfirmasi verifikasi.',
                'is_active' => true,
            ]
        );

        PortfolioPaymentMethod::updateOrCreate(
            ['bank_name' => 'Bank Mandiri'],
            [
                'account_number' => '1310029381023',
                'account_holder' => 'PT LUNOU TEKNOLOGI KREATIF',
                'instructions' => 'Gunakan nomor referensi atau order code pada berita transfer untuk mempercepat proses pemeriksaan admin.',
                'is_active' => true,
            ]
        );

        PortfolioPaymentMethod::updateOrCreate(
            ['bank_name' => 'QRIS & E-Wallet (GoPay, OVO, ShopeePay)'],
            [
                'account_number' => '085862319524',
                'account_holder' => 'LUNOU CREATIVE INDONESIA',
                'instructions' => 'Pindai kode QR atau transfer ke akun e-wallet resmi. Unggah tangkapan layar (screenshot) bukti berhasil.',
                'qr_image_url' => '/icon/1.png',
                'is_active' => true,
            ]
        );

        // 4. Global Platform Settings
        PortfolioSetting::set('max_pdf_files', 10);
        PortfolioSetting::set('max_pdf_size_mb', 5);
        PortfolioSetting::set('terms_and_conditions', "### 1. Ketentuan Umum Layanan Portfolio LUNOU\n"
            ."Platform LUNOU Portfolio Builder disediakan untuk memudahkan pengguna menyusun, mendokumentasikan, dan mempublikasikan karya personal, dokumen portofolio, dan materi profesional secara digital.\n\n"
            ."### 2. Hak Cipta & Kepemilikan Konten\n"
            ."Pengguna memegang penuh hak cipta atas seluruh karya, foto, teks, desain, dan dokumen PDF yang diunggah ke LUNOU Portfolio. Pengguna bertanggung jawab penuh memastikan tidak ada pelanggaran hak kekayaan intelektual (HAKI) milik pihak ketiga.\n\n"
            ."### 3. Kebijakan Pembayaran & Aktivasi Akses Premium\n"
            ."Akses fitur premium LUNOU Portfolio diaktifkan setelah bukti transfer manual diverifikasi dan disetujui oleh admin LUNOU. Sistem kami memvalidasi transfer secara cermat demi keamanan kedua belah pihak. Durasi akses berlaku sesuai paket yang dipilih dan dapat diperpanjang kapan saja.\n\n"
            ."### 4. Batasan Pengunggahan File\n"
            ."Setiap pengguna berhak mengunggah dokumen PDF hingga 10 file dengan ukuran maksimal 5 MB per berkas. Format gambar yang didukung meliputi JPG, PNG, dan WebP.\n\n"
            ."### 5. Keamanan Data & Privasi Pengguna\n"
            ."Seluruh data sensitif transaksi keuangan dan data pengguna dilindungi dengan kriptografi dan enkripsi tingkat tinggi di basis data LUNOU.\n");

        // 5. Entitlement & Sample Portfolio for Demo User (Tri)
        $userTri = User::where('email', 'tri@lunou.id')->first();
        if ($userTri) {
            PortfolioEntitlement::updateOrCreate(
                ['user_id' => $userTri->id],
                [
                    'status' => 'active',
                    'starts_at' => now(),
                    'expires_at' => now()->addDays(90),
                    'max_pdf_files' => 10,
                    'max_pdf_size_mb' => 5,
                ]
            );

            $portfolio = Portfolio::updateOrCreate(
                ['user_id' => $userTri->id],
                [
                    'title' => 'Tri Wicaksono · Product Designer & Creative Engineer',
                    'slug' => 'tri',
                    'bio' => 'Membantu startup dan brand membangun produk digital bernilai tinggi dengan sentuhan estetika minimalis dan teknologi interaktif.',
                    'theme' => 'minimal-clean',
                    'is_published' => true,
                    'published_at' => now(),
                    'views_count' => 128,
                    'settings' => [
                        'allow_download' => true,
                        'flipbook_default' => true,
                        'accent_color' => '#2563eb',
                        'font_family' => 'sans',
                    ],
                ]
            );

            // Seed Page 1: Cover Page
            PortfolioPage::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'sort_order' => 1],
                [
                    'title' => 'Cover & Hero',
                    'page_type' => 'cover',
                    'content' => [
                        'headline' => 'Desain Berjiwa. Karya yang Bermakna.',
                        'subheadline' => 'Koleksi Portofolio Terpilih 2024 - 2026',
                        'author_name' => 'Tri Wicaksono',
                        'author_title' => 'Senior UI/UX Designer & Product Technologist',
                        'cover_image' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80',
                        'cta_text' => 'Jelajahi Proyek Saya',
                        'location' => 'Jakarta, Indonesia',
                    ],
                ]
            );

            // Seed Page 2: About Me
            PortfolioPage::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'sort_order' => 2],
                [
                    'title' => 'Tentang Saya',
                    'page_type' => 'about',
                    'content' => [
                        'heading' => 'Merangkai Pengalaman Pengguna yang Berkesan',
                        'bio_paragraph' => 'Lebih dari 6 tahun berkecimpung di dunia desain produk digital, mobile apps, dan interaksi fisik NFC. Pendekatan saya memadukan estetika bersih dengan fungsionalitas intuitif.',
                        'skills' => ['UI/UX Design', 'Design Systems', 'Prototyping', 'User Research', 'Creative Direction', 'NFC Hardware Interaction'],
                        'experience' => [
                            ['role' => 'Lead Product Designer', 'company' => 'Lunou Creative Lab', 'period' => '2023 - Sekarang'],
                            ['role' => 'Senior UI Designer', 'company' => 'Studio Antigravity', 'period' => '2021 - 2023'],
                        ],
                    ],
                ]
            );

            // Seed Page 3: Featured Projects
            PortfolioPage::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'sort_order' => 3],
                [
                    'title' => 'Proyek & Karya',
                    'page_type' => 'projects',
                    'content' => [
                        'heading' => 'Karya Terpilih',
                        'description' => 'Eksplorasi produk digital, antarmuka seluler, dan kartu pintar interaktif.',
                        'items' => [
                            [
                                'title' => 'YOIMO NFC Social Ecosystem',
                                'category' => 'Hardware & Mobile UX',
                                'description' => 'Desain sistem kartu pintar gantungan kunci tap-and-connect yang menghubungkan identitas digital secara instan.',
                                'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=800&q=80',
                                'role' => 'Lead Product Designer & IoT Engineer',
                                'client' => 'Lunou Ecosystem',
                                'year' => '2026',
                                'tools' => ['Figma', 'NFC Hardware', 'Vue 3', 'Tailwind CSS', 'Laravel'],
                                'challenge' => 'Menjembatani interaksi fisik dan digital agar networking di acara offline dapat dilakukan dalam 1 detik tanpa repot mengetik username atau menginstal aplikasi pihak ketiga.',
                                'solution' => 'Mengintegrasikan chip NFC berfrekuensi 13.56MHz ke dalam gantungan kunci resin premium yang secara otomatis mengarahkan sensor HP ke profil me.myoin.id/username terenkripsi.',
                                'result' => 'Pengurangan waktu bertukar kontak hingga 90%, peningkatan konversi koneksi LinkedIn & portfolio sebesar 340%, dan dipakai di 50+ event komunitas kreatif.',
                                'case_study' => "YOIMO NFC lahir dari kebutuhan akan cara berjejaring yang lebih cepat, elegan, dan ramah lingkungan. Daripada mencetak ratusan kartu nama konvensional berbahan kertas yang cepat usang dan terbuang, satu perangkat pintar tap-and-connect memungkinkan kreator membagikan seluruh portofolio, media sosial, nomor WhatsApp, dan dokumen penting dalam satu ketukan instan ke smartphone lawan bicara.\n\nSistem ini dirancang dengan arsitektur backend yang sangat ringan, responsif dalam hitungan milidetik, serta dilengkapi fitur verifikasi kepemilikan anti-kloning melalui token dinamis.",
                                'link' => 'https://lunou.id',
                                'link_label' => 'Kunjungi Situs Resmi',
                            ],
                            [
                                'title' => 'Zenith Minimalist Finance App',
                                'category' => 'Fintech & Design System',
                                'description' => 'Aplikasi pencatatan keuangan pribadi dengan tipografi tajam, dark mode elegan, dan micro-interaction responsif.',
                                'image' => 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?auto=format&fit=crop&w=800&q=80',
                                'role' => 'UI/UX Designer & Design System Architect',
                                'client' => 'Zenith Financial Labs',
                                'year' => '2025 - 2026',
                                'tools' => ['Figma Tokens', 'Design System', 'Tailwind CSS', 'Micro-interactions'],
                                'challenge' => 'Kebanyakan aplikasi keuangan pribadi terasa rumit, penuh grafik berantakan yang membingungkan pengguna pemula, serta memiliki beban kognitif tinggi.',
                                'solution' => 'Merancang visual monokromatis dengan hierarki tipografi kontras tinggi, navigasi satu jempol (thumb-zone ergonomics), dan visualisasi data modular yang intuitif.',
                                'result' => 'Tingkat retensi pengguna 30 hari meningkat sebesar 48%, skor kepuasan CSAT mencapai 4.9/5.0, dan diadopsi sebagai acuan design system standar untuk 3 produk turunan.',
                                'case_study' => "Zenith Minimalist Finance App berfokus pada ketenangan pikiran saat mengelola keuangan pribadi. Pendekatan desain didasarkan pada prinsip minimalis dan ritme spasial yang lega, menghilangkan distraksi visual agar pengguna dapat mencatat pengeluaran dalam waktu kurang dari 3 detik.\n\nSetiap komponen antarmuka didukung tokens yang konsisten di berbagai breakpoint layar, memastikan kenyamanan penggunaan baik di perangkat smartphone berlayar kecil maupun tablet lipat modern.",
                                'link' => 'https://behance.net',
                                'link_label' => 'Kunjungi Behance',
                            ],
                        ],
                    ],
                ]
            );

            // Seed Page 4: PDF Document Showcase with Flipbook
            PortfolioPage::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'sort_order' => 4],
                [
                    'title' => 'Dokumen & CV Resmi',
                    'page_type' => 'pdf_book',
                    'content' => [
                        'heading' => 'Buku Portofolio & Dokumen Resmi',
                        'description' => 'Baca langsung berkas kurikulum vitae dan portofolio cetak saya dalam mode buku digital interaktif di bawah ini.',
                        'document_title' => 'Curriculum Vitae & Selected Works 2026.pdf',
                        'pdf_url' => '/sample-portfolio.pdf',
                        'allow_download' => true,
                        'file_size' => '2.4 MB',
                        'pages_count' => 8,
                    ],
                ]
            );

            // Seed Page 5: Contact & Collaboration
            PortfolioPage::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'sort_order' => 5],
                [
                    'title' => 'Kontak & Kolaborasi',
                    'page_type' => 'contact',
                    'content' => [
                        'heading' => 'Mari Bekerja Sama',
                        'subheading' => 'Terbuka untuk proyek freelance, konsultasi desain produk, dan kolaborasi kreatif.',
                        'email' => 'tri@lunou.id',
                        'whatsapp' => '085862319524',
                        'socials' => [
                            ['platform' => 'Instagram', 'handle' => '@tri.design', 'url' => 'https://instagram.com'],
                            ['platform' => 'LinkedIn', 'handle' => 'Tri Wicaksono', 'url' => 'https://linkedin.com'],
                            ['platform' => 'Behance', 'handle' => 'triwicaksono', 'url' => 'https://behance.net'],
                        ],
                    ],
                ]
            );
        }
    }
}
