<?php

use App\Http\Controllers\Akuntansi\AsetTetapController;
use App\Http\Controllers\Akuntansi\ConfigShuController;
use App\Http\Controllers\Akuntansi\JurnalController;
use App\Http\Controllers\Akuntansi\LaporanController;
use App\Http\Controllers\Akuntansi\TutupBulanController;
use App\Http\Controllers\Akuntansi\TutupTahunController;
use App\Http\Controllers\Akuntansi\SaldoAwalController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Gudang\GudangDashboardController;
use App\Http\Controllers\Gudang\PencocokanSurplusDefisitController;
use App\Http\Controllers\Gudang\BatchController;
use App\Http\Controllers\Gudang\KartuStokController;
use App\Http\Controllers\Gudang\KerusakanBarangController;
use App\Http\Controllers\Gudang\OpnameController;
use App\Http\Controllers\Gudang\PenerimaanBarangController;
use App\Http\Controllers\Gudang\StokController;
use App\Http\Controllers\Keuangan\HutangController;
use App\Http\Controllers\Keuangan\KasTransaksiController;
use App\Http\Controllers\Keuangan\PelunasanController;
use App\Http\Controllers\Keuangan\PiutangController;
use App\Http\Controllers\Keuangan\SimpananController;
use App\Http\Controllers\Konsinyasi\PengirimanKonsinyasiController;
use App\Http\Controllers\Konsinyasi\RekonsiliasiController;
use App\Http\Controllers\Konsinyasi\ReturKonsinyasiController;
use App\Http\Controllers\Konsinyasi\SetoranKonsinyasiController;
use App\Http\Controllers\Konsinyasi\StokKonsinyasiController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Master\BarangController;
use App\Http\Controllers\Master\CoaController;
use App\Http\Controllers\Master\GudangController as MasterGudangController;
use App\Http\Controllers\Master\KasBankController;
use App\Http\Controllers\Master\KomoditasController;
use App\Http\Controllers\Master\EntitasController;
use App\Http\Controllers\Master\PeriodeController;
use App\Http\Controllers\Master\PihakController;
use App\Http\Controllers\Master\SatuanController;
use App\Http\Controllers\Pembelian\PembelianController;
use App\Http\Controllers\Pembelian\ReturPembelianController;
use App\Http\Controllers\Penjualan\PenjualanController;
use App\Http\Controllers\Penjualan\ReturPenjualanController;
use App\Http\Controllers\Perencanaan\DashboardM2Controller;
use App\Http\Controllers\Perencanaan\DemografiController;
use App\Http\Controllers\Perencanaan\KebutuhanKomoditasController;
use App\Http\Controllers\Perencanaan\LaporanM2Controller;
use App\Http\Controllers\Perencanaan\NeracaKomoditasController;
use App\Http\Controllers\Perencanaan\PerbandinganHargaController;
use App\Http\Controllers\Perencanaan\PermintaanPengadaanController;
use App\Http\Controllers\Perencanaan\PotensiProduksiController;
use App\Http\Controllers\Perencanaan\StandarKebutuhanController;
use Illuminate\Support\Facades\Route;

Route::get('/', \App\Http\Controllers\LandingController::class)->name('landing');

Route::middleware('auth')->get('/home', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

// ===== Modul Survei Lapangan KDMP =====
require __DIR__ . '/survei.php';

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== SUPER ADMIN =====
    Route::middleware('super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::post('/approve/{id}', [\App\Http\Controllers\Admin\SuperAdminController::class, 'approve'])->name('approve');
    });

    // ===== M0 â€” Master & Periode =====
    Route::prefix('master')->name('master.')->group(function () {
        // Daftar seluruh entitas (lintas desa) hanya untuk super admin.
        Route::resource('entitas', EntitasController::class)->middleware('super_admin');
        Route::resource('komoditas', KomoditasController::class);
        Route::resource('satuan', SatuanController::class);
        Route::get('barang/stok-minimum', [\App\Http\Controllers\Master\StokMinimumBarangController::class, 'index'])
            ->name('barang.stok-minimum.index');
        Route::patch('barang/stok-minimum/{idBarang}', [\App\Http\Controllers\Master\StokMinimumBarangController::class, 'update'])
            ->name('barang.stok-minimum.update');
        Route::resource('barang', BarangController::class);
        Route::resource('pihak', PihakController::class);
        Route::resource('kas-bank', KasBankController::class);
        Route::resource('gudang', MasterGudangController::class);
        Route::resource('periode', PeriodeController::class)->only(['index', 'store', 'edit', 'update']);
        Route::resource('coa', CoaController::class);
    });



    // ===== M2/M3 â€” Kalkulasi Kebutuhan & Perencanaan =====
    Route::prefix('perencanaan')->name('perencanaan.')->group(function () {
        Route::get('dashboard', [DashboardM2Controller::class, 'index'])->name('dashboard');
        Route::resource('demografi', DemografiController::class);
        Route::resource('potensi-produksi', PotensiProduksiController::class);
        Route::resource('standar-kebutuhan', StandarKebutuhanController::class);
        Route::post('kebutuhan-komoditas/kalkulasi', [KebutuhanKomoditasController::class, 'kalkulasi'])
            ->name('kebutuhan-komoditas.kalkulasi');
        Route::resource('kebutuhan-komoditas', KebutuhanKomoditasController::class)
            ->only(['index', 'show']);
        Route::resource('neraca-komoditas', NeracaKomoditasController::class)
            ->only(['index', 'show']);
        Route::get('laporan', [LaporanM2Controller::class, 'index'])->name('laporan.index');
        Route::resource('perbandingan-harga', PerbandinganHargaController::class);
        Route::get('permintaan-pengadaan/from-hasil/{hasil}', [PermintaanPengadaanController::class, 'fromHasil'])
            ->name('permintaan-pengadaan.from-hasil');
        Route::resource('permintaan-pengadaan', PermintaanPengadaanController::class);
    });

    // ===== M4 â€” Gudang =====
    Route::prefix('gudang')->name('gudang.')->group(function () {
        Route::get('/', [GudangDashboardController::class, 'index'])->name('index');
        Route::get('pencocokan-surplus-defisit', [PencocokanSurplusDefisitController::class, 'index'])
            ->name('pencocokan-surplus-defisit.index');
        Route::get('batch', [BatchController::class, 'index'])->name('batch.index');
        Route::resource('penerimaan', PenerimaanBarangController::class);
        Route::resource('opname', OpnameController::class);
        Route::resource('kerusakan', KerusakanBarangController::class);
        Route::resource('kartu-stok', KartuStokController::class)->only(['index', 'show']);
        Route::resource('stok', StokController::class)->only(['index', 'show']);
    });

    // ===== M5 Ã¢â‚¬â€ Pembelian =====
    Route::prefix('pembelian')->name('pembelian.')->group(function () {
    Route::resource('pembelian', PembelianController::class);

    Route::patch('pembelian/{pembelian}/approve', [PembelianController::class, 'approve'])
        ->name('approve');

    Route::patch('pembelian/{pembelian}/cancel', [PembelianController::class, 'cancel'])
        ->name('cancel');

    Route::get('pembelian/{pembelian}/grn/create', [PembelianController::class, 'createGrn'])
        ->name('create-grn');

    Route::post('pembelian/{pembelian}/grn', [PembelianController::class, 'storeGrn'])
        ->name('store-grn');

    Route::get('pembelian/{pembelian}/retur', [PembelianController::class, 'showRetur'])
        ->name('show-retur');

    Route::resource('retur', ReturPembelianController::class);
    Route::patch('retur/{retur}/approve', [ReturPembelianController::class, 'approve'])
        ->name('retur.approve');
    });

    // ===== M6 â€” Penjualan =====
    Route::prefix('penjualan')->name('penjualan.')->group(function () {
        Route::resource('penjualan', PenjualanController::class);
        Route::resource('retur', ReturPenjualanController::class);
    });

    // ===== M7 â€” Konsinyasi Antar Desa =====
    Route::prefix('konsinyasi')->name('konsinyasi.')->group(function () {
        Route::get('pengiriman/{id}/posting', [PengirimanKonsinyasiController::class, 'postingPage'])
            ->name('pengiriman.posting-page');
        Route::post('pengiriman/{id}/posting', [PengirimanKonsinyasiController::class, 'posting'])
            ->name('pengiriman.posting');
        Route::resource('pengiriman', PengirimanKonsinyasiController::class);
        Route::resource('stok', StokKonsinyasiController::class)->only(['index', 'show']);
        Route::resource('retur', ReturKonsinyasiController::class)->only(['index', 'create', 'store']);
        Route::resource('setoran', SetoranKonsinyasiController::class);
        Route::get('rekonsiliasi', [RekonsiliasiController::class, 'index'])->name('rekonsiliasi.index');
    });

    // ===== M8/M9 â€” Keuangan & Akuntansi (fokus pendalaman besok) =====
    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        Route::resource('piutang', PiutangController::class)->only(['index', 'show']);
        Route::resource('hutang', HutangController::class)->only(['index', 'show']);
        // AJAX: ambil piutang/hutang terbuka milik pihak tertentu (harus SEBELUM resource)
        Route::get('pelunasan/pihak/{pihak}/terbuka', [PelunasanController::class, 'terbuka'])
            ->name('pelunasan.terbuka');
        // Pelunasan tidak bisa di-edit/hapus setelah posted â€” pembatalan via jurnal balik
        Route::resource('pelunasan', PelunasanController::class)->except(['edit', 'update', 'destroy']);
        Route::resource('kas-transaksi', KasTransaksiController::class);
        Route::resource('simpanan', SimpananController::class);
    });

    Route::prefix('akuntansi')->name('akuntansi.')->group(function () {
        Route::resource('jurnal', JurnalController::class)->only(['index', 'show']);
        Route::post('jurnal/{id}/balik', [JurnalController::class, 'balik'])->name('jurnal.balik');
        Route::get('saldo-awal', [SaldoAwalController::class, 'index'])->name('saldo-awal.index');
        Route::post('saldo-awal', [SaldoAwalController::class, 'store'])->name('saldo-awal.store');
        Route::resource('aset-tetap', AsetTetapController::class);
        // Pembagian SHU hanya untuk Koperasi; entitas lain dialokasikan otomatis saat Tutup Tahun.
        Route::middleware(\App\Http\Middleware\HanyaKoperasi::class)->group(function () {
            Route::resource('config-shu', ConfigShuController::class);
        });

        Route::get('tutup-bulan', [TutupBulanController::class, 'index'])->name('tutup-bulan.index');
        Route::post('tutup-bulan', [TutupBulanController::class, 'store'])->name('tutup-bulan.store');
        Route::get('tutup-tahun', [TutupTahunController::class, 'index'])->name('tutup-tahun.index');
        Route::post('tutup-tahun', [TutupTahunController::class, 'store'])->name('tutup-tahun.store');

        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('neraca-saldo', [LaporanController::class, 'neracaSaldo'])->name('neraca-saldo');
            Route::get('buku-besar', [LaporanController::class, 'bukuBesar'])->name('buku-besar');
            Route::get('neraca', [LaporanController::class, 'neraca'])->name('neraca');
            Route::get('laba-rugi', [LaporanController::class, 'labaRugi'])->name('laba-rugi');
            Route::get('arus-kas', [LaporanController::class, 'arusKas'])->name('arus-kas');
        });
    });
});

Route::post('/admin/set-entitas', function (\Illuminate\Http\Request $request) {
    if (auth()->user()->id_entitas === null) {
        if ($request->id_entitas) {
            $request->session()->put('entitas_aktif_pilihan', $request->id_entitas);
        } else {
            $request->session()->forget('entitas_aktif_pilihan');
        }
    }
    return back();
})->name('admin.set_entitas')->middleware(['auth', 'super_admin']);
