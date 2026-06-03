# Panduan Praktis Membuat PPT Demo (Berdasarkan Outline Terbaru)

## 1) Setup Deck (10 menit)
- Slide size: `16:9` (Widescreen).
- Font saran: `Calibri` atau `Aptos` (konsisten sampai akhir).
- Skema warna:
  - Biru tua untuk judul.
  - Abu netral untuk body.
  - Hijau untuk state sukses (`paid`).
  - Kuning untuk state proses (`pending_review`, `awaiting_payment`).
  - Merah/gelap untuk state penutupan (`rejected`, `cancelled_*`).
- Simpan sebagai: `demo-rental-system-progress.pptx`.

## 2) Ambil Screenshot Bahan (20-30 menit)
- Login: `/login`
- Admin: `/admin/users`, `/admin/transactions`, `/admin/login-audit`
- Agent: `/agent/properties`, `/agent/rental-requests`
- Tenant: `/houses`, `/houses/{id}`, `/tenant/requests`, `/tenant/contracts`, `/tenant/contracts/extensions`
- Chat: `/messages`, `/messages/{requestId}` atau `/messages/conversations/{id}`
- Gunakan zoom browser `100%` dan resolusi rekam/screenshot `1920x1080`.

## 3) Bangun Slide Berurutan (45-60 menit)
- Ikuti konten per slide di file:
  - `thesis-report/demo-slide-outline.md`
- Pola layout yang cepat:
  - Slide naratif: judul + 3 bullet utama.
  - Slide bukti UI: judul + 1 screenshot besar atau 2 screenshot sejajar.
  - Slide flow: judul + diagram kiri + poin validasi kanan.

## 4) Hal yang Wajib Akurat Saat Presentasi
- Payment lock `awaiting_payment` punya batas waktu `7 hari` (`payment_due_at`).
- Scheduler berjalan `setiap 5 menit`.
- Request overdue di payment stage berubah ke `cancelled_lost`.
- Transaksi `initial_rent` yang belum dibayar ditandai `failed` saat lock expire/cancel.
- Extension menggunakan transaksi terpisah: `extension_rent`.

## 5) Urutan Demo Lisan (8-12 menit)
- Urutan aman:
  - Tenant discovery -> submit request
  - Agent approve -> masuk `awaiting_payment`
  - Tenant pay -> contract tampil
  - Extension request -> approve -> pay
  - Admin monitoring dan login audit
  - Tutup dengan next step analytics + test coverage

## 6) Final QA Sebelum Dipresentasikan
- Cek semua URL dan nama status sama dengan implementasi.
- Pastikan screenshot tidak menampilkan data dummy yang membingungkan.
- Pastikan slide 15-17 konsisten: current build vs next milestone.
- Rehearsal 1 kali dengan timer maksimal 12 menit.
