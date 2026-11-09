<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modul Survei Routes (Kantong Fitur Mandiri)
|--------------------------------------------------------------------------
|
| File route ini mencakup seluruh endpoint admin dan publik untuk modul
| survei lapangan (KDMP). Cukup panggil file ini di routes/web.php dengan:
| require __DIR__ . '/survei.php'; atau taruh di Provider route Anda.
|
*/

// Namespace controller survei terpusat di App\Http\Controllers\Survei
$cSurvei = '\App\Http\Controllers\Survei\\';

// =========================================================================
// 1. RUTE PUBLIK (Tanpa Login / Berbasis Token Unik)
// =========================================================================

// --- Survei RT (Ketua RT / Baseline KK) ---
Route::get('/survei/isi/rt/{token}', [$cSurvei . 'PublicSurveiRtController', 'show'])->name('survei.public.rt.show');
Route::post('/survei/isi/rt/{token}', [$cSurvei . 'PublicSurveiRtController', 'store'])->name('survei.public.rt.store');
Route::put('/survei/isi/rt/{token}/{id}', [$cSurvei . 'PublicSurveiRtController', 'update'])->name('survei.public.rt.update');

// --- Survei Produsen (Kelompok Tani & Ekraf) ---
Route::get('/survei/isi/produsen/{token}', [$cSurvei . 'PublicSurveiProdusenController', 'show'])->name('survei.public.produsen.show');
Route::post('/survei/isi/produsen/{token}', [$cSurvei . 'PublicSurveiProdusenController', 'store'])->name('survei.public.produsen.store');
Route::delete('/survei/isi/produsen/{token}/{id}', [$cSurvei . 'PublicSurveiProdusenController', 'destroy'])->name('survei.public.produsen.destroy');

// --- Survei Masyarakat (Multi-Step Form: Biodata, Anggota KK, Konsumsi, Review) ---
// Step 1: Identitas KK & Geocoding Alamat / GPS
Route::get('/survei/isi/masyarakat/{token}', [$cSurvei . 'PublicSurveiMasyarakatController', 'show'])
    ->name('survei.public.masyarakat.show');
Route::post('/survei/isi/masyarakat/{token}', [$cSurvei . 'PublicSurveiMasyarakatController', 'store'])
    ->name('survei.public.masyarakat.store');

// Step 2: Anggota Keluarga (Usia & Gender)
Route::get('/survei/isi/masyarakat/{token}/{id_masyarakat}/anggota', [$cSurvei . 'PublicSurveiMasyarakatController', 'showAnggota'])
    ->name('survei.public.masyarakat.anggota.show');
Route::post('/survei/isi/masyarakat/{token}/{id_masyarakat}/anggota', [$cSurvei . 'PublicSurveiMasyarakatController', 'storeAnggota'])
    ->name('survei.public.masyarakat.anggota.store');

// Step 3: Konsumsi Keluarga (Kebutuhan Pangan + Web Speech API)
Route::get('/survei/isi/masyarakat/{token}/{id_masyarakat}/sesi3', [$cSurvei . 'PublicMasyarakatSesi3Controller', 'show'])
    ->name('survei.public.masyarakat.sesi3.show');
Route::post('/survei/isi/masyarakat/{token}/{id_masyarakat}/sesi3', [$cSurvei . 'PublicMasyarakatSesi3Controller', 'store'])
    ->name('survei.public.masyarakat.sesi3.store');

// Step 4: Ringkasan Review & Final Submit
Route::get('/survei/isi/masyarakat/{token}/{id_masyarakat}/review', [$cSurvei . 'PublicMasyarakatReviewController', 'show'])
    ->name('survei.public.masyarakat.review.show');
Route::post('/survei/isi/masyarakat/{token}/{id_masyarakat}/submit', [$cSurvei . 'PublicMasyarakatReviewController', 'submit'])
    ->name('survei.public.masyarakat.review.submit');

// Step 5: Halaman Terima Kasih
Route::get('/survei/isi/masyarakat/{token}/terimakasih', [$cSurvei . 'PublicMasyarakatReviewController', 'terimakasih'])
    ->name('survei.public.masyarakat.terimakasih');

// --- Survei Publik Sesi 1-4 (Alternatif / Legacy) ---
Route::get('/survei/isi/{token}', [$cSurvei . 'PublicSurveiController', 'show'])->name('survei.public.show');
Route::post('/survei/isi/{token}', [$cSurvei . 'PublicSurveiController', 'store'])->name('survei.public.store');
Route::get('/survei/isi/{token}/modul/{modul}', [$cSurvei . 'PublicSurveiController', 'showModul'])->name('survei.public.modul.show');
Route::post('/survei/isi/{token}/modul/{modul}', [$cSurvei . 'PublicSurveiController', 'storeModul'])->name('survei.public.modul.store');

Route::get('/survei/isi/{token}/sesi1', [$cSurvei . 'PublicSesi1Controller', 'show'])->name('survei.public.sesi1.show');
Route::post('/survei/isi/{token}/sesi1/narasumber', [$cSurvei . 'PublicSesi1Controller', 'storeNarasumber'])->name('survei.public.sesi1.narasumber.store');
Route::put('/survei/isi/{token}/sesi1/narasumber/{narasumber}', [$cSurvei . 'PublicSesi1Controller', 'updateNarasumber'])->name('survei.public.sesi1.narasumber.update');
Route::get('/survei/isi/{token}/sesi1/narasumber/{narasumber}', [$cSurvei . 'PublicSesi1Controller', 'showNarasumber'])->name('survei.public.sesi1.narasumber.show');
Route::post('/survei/isi/{token}/sesi1/narasumber/{narasumber}/demografi', [$cSurvei . 'PublicSesi1Controller', 'storeDemografi'])->name('survei.public.sesi1.demografi.store');
Route::delete('/survei/isi/{token}/sesi1/narasumber/{narasumber}', [$cSurvei . 'PublicSesi1Controller', 'destroyNarasumber'])->name('survei.public.sesi1.narasumber.destroy');
Route::post('/survei/isi/{token}/sesi1/selesaikan', [$cSurvei . 'PublicSesi1Controller', 'selesaikan'])->name('survei.public.sesi1.selesaikan');
Route::post('/survei/isi/{token}/sesi1/salin-sebelumnya', [$cSurvei . 'PublicSesi1Controller', 'salinSebelumnya'])->name('survei.public.sesi1.salin_sebelumnya');

Route::get('/survei/isi/{token}/sesi2', [$cSurvei . 'PublicSesi2Controller', 'show'])->name('survei.public.sesi2.show');
Route::post('/survei/isi/{token}/sesi2/narasumber', [$cSurvei . 'PublicSesi2Controller', 'storeNarasumber'])->name('survei.public.sesi2.narasumber.store');
Route::post('/survei/isi/{token}/sesi2/produksi', [$cSurvei . 'PublicSesi2Controller', 'storeProduksi'])->name('survei.public.sesi2.produksi.store');
Route::put('/survei/isi/{token}/sesi2/produksi/{produksi}', [$cSurvei . 'PublicSesi2Controller', 'updateProduksi'])->name('survei.public.sesi2.produksi.update');
Route::delete('/survei/isi/{token}/sesi2/produksi/{produksi}', [$cSurvei . 'PublicSesi2Controller', 'destroyProduksi'])->name('survei.public.sesi2.produksi.destroy');
Route::post('/survei/isi/{token}/sesi2/simpan-draft', [$cSurvei . 'PublicSesi2Controller', 'simpanDraft'])->name('survei.public.sesi2.draft');
Route::post('/survei/isi/{token}/sesi2/selesaikan', [$cSurvei . 'PublicSesi2Controller', 'selesaikan'])->name('survei.public.sesi2.selesaikan');

Route::get('/survei/isi/{token}/sesi3', [$cSurvei . 'PublicSesi3Controller', 'show'])->name('survei.public.sesi3.show');
Route::post('/survei/isi/{token}/sesi3/standar', [$cSurvei . 'PublicSesi3Controller', 'store'])->name('survei.public.sesi3.standar.store');
Route::post('/survei/isi/{token}/sesi3/salin-dari-sesi2', [$cSurvei . 'PublicSesi3Controller', 'salinDariSesi2'])->name('survei.public.sesi3.salin.sesi2');
Route::delete('/survei/isi/{token}/sesi3/standar', [$cSurvei . 'PublicSesi3Controller', 'destroy'])->name('survei.public.sesi3.standar.destroy');
Route::post('/survei/isi/{token}/sesi3/simpan-draft', [$cSurvei . 'PublicSesi3Controller', 'simpanDraft'])->name('survei.public.sesi3.draft');
Route::post('/survei/isi/{token}/sesi3/selesaikan', [$cSurvei . 'PublicSesi3Controller', 'selesaikan'])->name('survei.public.sesi3.selesaikan');

Route::get('/survei/isi/{token}/sesi4', [$cSurvei . 'PublicSesi4Controller', 'show'])->name('survei.public.sesi4.show');
Route::post('/survei/isi/{token}/sesi4/pemenuhan', [$cSurvei . 'PublicSesi4Controller', 'store'])->name('survei.public.sesi4.pemenuhan.store');
Route::delete('/survei/isi/{token}/sesi4/pemenuhan/{pemenuhan}', [$cSurvei . 'PublicSesi4Controller', 'destroy'])->name('survei.public.sesi4.pemenuhan.destroy');
Route::post('/survei/isi/{token}/sesi4/simpan-draft', [$cSurvei . 'PublicSesi4Controller', 'simpanDraft'])->name('survei.public.sesi4.draft');
Route::post('/survei/isi/{token}/sesi4/selesaikan', [$cSurvei . 'PublicSesi4Controller', 'selesaikan'])->name('survei.public.sesi4.selesaikan');


// =========================================================================
// 2. RUTE ADMIN MANAJEMEN SURVEI (Membutuhkan Login / Auth)
// =========================================================================

Route::middleware('auth')->group(function () use ($cSurvei) {
    Route::prefix('survei')->name('survei.')->group(function () use ($cSurvei) {
        
        // ─── Dashboard Survei (Induk Menu: http://127.0.0.1:8000/survei/dashboard) ───
        Route::view('dashboard', 'survei.dashboard')->name('dashboard.index');

        // ─── Card 1: Daftar Sesi Survei ───
        Route::resource('sesi', $cSurvei . 'SesiSurveiController');
        Route::post('sesi/{sesi}/sinkron', [$cSurvei . 'SesiSurveiController', 'sinkron'])->name('sesi.sinkron');

        // ─── Card 2: Master & Baseline Data RT ───
        $cRt = class_exists(\App\Http\Controllers\Survei\RtDesaController::class)
            ? \App\Http\Controllers\Survei\RtDesaController::class
            : $cSurvei . 'RtDesaController';
        Route::resource('rt', $cRt);

        // ─── Card 3: Produsen Desa (Kelompok Tani & Ekraf) ───
        $cProdusen = class_exists(\App\Http\Controllers\Survei\ProdusenDesaController::class)
            ? \App\Http\Controllers\Survei\ProdusenDesaController::class
            : $cSurvei . 'ProdusenDesaController';
        Route::resource('produsen', $cProdusen);

        // Hapus entri komoditas produksi dari halaman admin
        Route::delete('produksi/{id}', function (int $id) {
            $item = \App\Models\Survei\ProduksiProdusen::findOrFail($id);
            $item->delete();
            return redirect()->route('survei.produsen.index')->with('success', 'Data komoditas berhasil dihapus.');
        })->name('produksi.destroy');

        // ─── Card 4: Validasi & Laporan Survei Masyarakat (Baseline vs Real) ───
        $cValidasi = class_exists(\App\Http\Controllers\Survei\ValidasiSurveiController::class)
            ? \App\Http\Controllers\Survei\ValidasiSurveiController::class
            : $cSurvei . 'ValidasiSurveiController';
        Route::get('validasi', [$cValidasi, 'index'])->name('validasi.index');
        Route::get('validasi/rt/{id_sesi}/{nama_rt}/{rw?}', [$cValidasi, 'showRtDetail'])->name('validasi.rt.show');
        Route::delete('validasi/masyarakat/{id}', [$cValidasi, 'destroyMasyarakat'])->name('validasi.masyarakat.destroy');

        // ─── Bank Pertanyaan (Opsional / Master) ───
        Route::resource('pertanyaan', $cSurvei . 'PertanyaanController');

        // ─── Sesi 1: Data Demografi Desa (Admin Review & Pengisian) ───
        Route::get('sesi/{sesi}/sesi1', [$cSurvei . 'Sesi1DemografiController', 'show'])->name('sesi1.show');
        Route::post('sesi/{sesi}/sesi1/narasumber', [$cSurvei . 'Sesi1DemografiController', 'storeNarasumber'])->name('sesi1.narasumber.store');
        Route::put('sesi/{sesi}/sesi1/narasumber/{narasumber}', [$cSurvei . 'Sesi1DemografiController', 'updateNarasumber'])->name('sesi1.narasumber.update');
        Route::get('sesi/{sesi}/sesi1/narasumber/{narasumber}', [$cSurvei . 'Sesi1DemografiController', 'showNarasumber'])->name('sesi1.narasumber.show');
        Route::post('sesi/{sesi}/sesi1/narasumber/{narasumber}/demografi', [$cSurvei . 'Sesi1DemografiController', 'storeDemografi'])->name('sesi1.demografi.store');
        Route::delete('sesi/{sesi}/sesi1/narasumber/{narasumber}', [$cSurvei . 'Sesi1DemografiController', 'destroyNarasumber'])->name('sesi1.narasumber.destroy');
        Route::post('sesi/{sesi}/sesi1/simpan-draft', [$cSurvei . 'Sesi1DemografiController', 'simpanDraft'])->name('sesi1.draft');
        Route::post('sesi/{sesi}/sesi1/selesaikan', [$cSurvei . 'Sesi1DemografiController', 'selesaikan'])->name('sesi1.selesaikan');
        Route::post('sesi/{sesi}/sesi1/salin-sebelumnya', [$cSurvei . 'Sesi1DemografiController', 'salinSebelumnya'])->name('sesi1.salin_sebelumnya');

        // ─── Sesi 2: Potensi Produksi Desa (Admin Review & Pengisian) ───
        Route::get('sesi/{sesi}/sesi2', [$cSurvei . 'Sesi2ProduksiController', 'show'])->name('sesi2.show');
        Route::post('sesi/{sesi}/sesi2/narasumber', [$cSurvei . 'Sesi2ProduksiController', 'storeNarasumber'])->name('sesi2.narasumber.store');
        Route::put('sesi/{sesi}/sesi2/narasumber/{narasumber}', [$cSurvei . 'Sesi2ProduksiController', 'updateNarasumber'])->name('sesi2.narasumber.update');
        Route::delete('sesi/{sesi}/sesi2/narasumber/{narasumber}', [$cSurvei . 'Sesi2ProduksiController', 'destroyNarasumber'])->name('sesi2.narasumber.destroy');
        Route::post('sesi/{sesi}/sesi2/produksi', [$cSurvei . 'Sesi2ProduksiController', 'storeProduksi'])->name('sesi2.produksi.store');
        Route::put('sesi/{sesi}/sesi2/produksi/{produksi}', [$cSurvei . 'Sesi2ProduksiController', 'updateProduksi'])->name('sesi2.produksi.update');
        Route::delete('sesi/{sesi}/sesi2/produksi/{produksi}', [$cSurvei . 'Sesi2ProduksiController', 'destroyProduksi'])->name('sesi2.produksi.destroy');
        Route::post('sesi/{sesi}/sesi2/simpan-draft', [$cSurvei . 'Sesi2ProduksiController', 'simpanDraft'])->name('sesi2.draft');
        Route::post('sesi/{sesi}/sesi2/selesaikan', [$cSurvei . 'Sesi2ProduksiController', 'selesaikan'])->name('sesi2.selesaikan');

        // ─── Sesi 3: Standar Konsumsi Komoditas (Admin Review & Pengisian) ───
        Route::get('sesi/{sesi}/sesi3', [$cSurvei . 'Sesi3StandarKonsumsiController', 'show'])->name('sesi3.show');
        Route::post('sesi/{sesi}/sesi3/standar', [$cSurvei . 'Sesi3StandarKonsumsiController', 'store'])->name('sesi3.standar.store');
        Route::post('sesi/{sesi}/sesi3/salin-dari-sesi2', [$cSurvei . 'Sesi3StandarKonsumsiController', 'salinDariSesi2'])->name('sesi3.salin.sesi2');
        Route::delete('sesi/{sesi}/sesi3/standar', [$cSurvei . 'Sesi3StandarKonsumsiController', 'destroy'])->name('sesi3.standar.destroy');
        Route::post('sesi/{sesi}/sesi3/simpan-draft', [$cSurvei . 'Sesi3StandarKonsumsiController', 'simpanDraft'])->name('sesi3.draft');
        Route::post('sesi/{sesi}/sesi3/selesaikan', [$cSurvei . 'Sesi3StandarKonsumsiController', 'selesaikan'])->name('sesi3.selesaikan');

        // ─── Sesi 4: Pemenuhan & Harga Komoditas (Admin Review & Pengisian) ───
        Route::get('sesi/{sesi}/sesi4', [$cSurvei . 'Sesi4PemenuhanController', 'show'])->name('sesi4.show');
        Route::post('sesi/{sesi}/sesi4/pemenuhan', [$cSurvei . 'Sesi4PemenuhanController', 'store'])->name('sesi4.pemenuhan.store');
        Route::delete('sesi/{sesi}/sesi4/pemenuhan/{pemenuhan}', [$cSurvei . 'Sesi4PemenuhanController', 'destroy'])->name('sesi4.pemenuhan.destroy');
        Route::post('sesi/{sesi}/sesi4/simpan-draft', [$cSurvei . 'Sesi4PemenuhanController', 'simpanDraft'])->name('sesi4.draft');
        Route::post('sesi/{sesi}/sesi4/selesaikan', [$cSurvei . 'Sesi4PemenuhanController', 'selesaikan'])->name('sesi4.selesaikan');
    });
});
