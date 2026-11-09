<?php

namespace App\Http\Controllers\Survei;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Survei\RtDesa;
use App\Models\Survei\MasyarakatDesa;
use App\Models\Survei\SesiSurvei;

class ValidasiSurveiController extends Controller
{
    private function getUserWilayahId(): ?int
    {
        $user = auth()->user();
        if (!$user || !$user->id_entitas || !$user->entitas) {
            return null;
        }

        return $user->entitas->id_wilayah;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $idWilayah = $this->getUserWilayahId();

        $periodsQuery = SesiSurvei::select('bulan', 'tahun');
        if ($idWilayah) {
            $periodsQuery->where('id_wilayah', $idWilayah);
        }
        $availablePeriods = $periodsQuery->distinct()
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->get();
            
        $bulan = $request->input('bulan', (int)date('n'));
        $tahun = $request->input('tahun', (int)date('Y'));

        // Filter RtDesa by selected period & admin's village
        $rtsQuery = RtDesa::with(['wilayah', 'sesi'])
            ->whereHas('sesi', function ($q) use ($bulan, $tahun, $idWilayah) {
                $q->where('bulan', (int)$bulan)
                  ->where('tahun', (int)$tahun);
                if ($idWilayah) {
                    $q->where('id_wilayah', $idWilayah);
                }
            });

        if ($idWilayah) {
            $rtsQuery->where('id_wilayah', $idWilayah);
        }

        $rts = $rtsQuery->get();

        $matchedMasyarakatIds = collect();

        foreach ($rts as $rt) {
            preg_match('/\d+/', $rt->nama_rt, $rtMatch);
            preg_match('/\d+/', $rt->rw, $rwMatch);
            
            $rt_digit = $rtMatch[0] ?? '';
            $rw_digit = $rwMatch[0] ?? '';
            
            // Get all MasyarakatDesa for this session & village with anggota that have completed consumption survey
            $masyarakatQuery = MasyarakatDesa::with('anggota')
                ->whereHas('konsumsi')
                ->where('id_sesi', $rt->id_sesi);

            if ($idWilayah) {
                $masyarakatQuery->where('id_wilayah', $idWilayah);
            } elseif ($rt->id_wilayah) {
                $masyarakatQuery->where('id_wilayah', $rt->id_wilayah);
            }
                
            $masyarakatDesaList = $masyarakatQuery->get()
                ->filter(function($m) use ($rt_digit, $rw_digit, $rt, $rts) {
                     $m_rt = preg_replace('/\D/', '', (string)$m->rt);
                     $m_rw = preg_replace('/\D/', '', (string)$m->rw);
                     
                     // Compare integers to avoid "01" vs "1" mismatch
                     $rt_match = (int)$m_rt === (int)$rt_digit;
                     
                     // If RT didn't provide RW, we don't strictly require it to match
                     $rw_match = true;
                     if (!empty($rt->rw) && $rw_digit !== '') {
                         $rw_match = (int)$m_rw === (int)$rw_digit;
                     }
                     
                     // Dusun opsional: hanya cocokkan jika ada RT ganda bernomor sama di desa ini
                     $dusun_match = true;
                     $isDuplicateRt = $rts->where('id_wilayah', $rt->id_wilayah)
                         ->filter(function($other) use ($rt_digit, $rw_digit) {
                             return (int)preg_replace('/\D/', '', $other->nama_rt) === (int)$rt_digit 
                                 && (int)preg_replace('/\D/', '', $other->rw) === (int)$rw_digit;
                         })->count() > 1;

                     if ($isDuplicateRt && !empty($rt->dusun) && !empty($m->dusun)) {
                         $clean_rt = strtolower(trim(str_ireplace('dusun ', '', $rt->dusun)));
                         $clean_m = strtolower(trim(str_ireplace('dusun ', '', $m->dusun)));
                         $dusun_match = ($clean_rt === $clean_m || str_contains($clean_rt, $clean_m) || str_contains($clean_m, $clean_rt));
                     }

                     // Pastikan nama desa cocok
                     $rt_desa_name = trim(str_ireplace('desa ', '', $rt->wilayah->nama ?? ''));
                     $m_desa_name = trim(str_ireplace('desa ', '', $m->desa ?? ''));
                     $desa_match = true;
                     if (!empty($rt_desa_name) && !empty($m_desa_name)) {
                         $desa_match = (strtolower($m_desa_name) === strtolower($rt_desa_name));
                     }

                     return $rt_match && $rw_match && $dusun_match && $desa_match;
                });
                
            $rt->kk_terdata = $masyarakatDesaList->count();
            
            $jiwa = 0;
            foreach ($masyarakatDesaList as $m) {
                // Tiap keluarga terdaftar: anggota sudah mencakup seluruh jiwa dalam KK (min 1)
                $jiwa += ($m->anggota->count() > 0 ? $m->anggota->count() : 1);
                $matchedMasyarakatIds->push($m->id);
            }
            $rt->jiwa_terdata = $jiwa;
            
            $rt->masyarakat_list = $masyarakatDesaList;
        }

        // Cari masyarakat yang sudah input tapi RT-nya belum mendaftar baseline
        $unmatchedQuery = MasyarakatDesa::with('anggota')
            ->whereHas('konsumsi')
            ->whereHas('sesi', function ($q) use ($bulan, $tahun, $idWilayah) {
                $q->where('bulan', (int)$bulan)->where('tahun', (int)$tahun);
                if ($idWilayah) $q->where('id_wilayah', $idWilayah);
            });
        if ($idWilayah) {
            $unmatchedQuery->where('id_wilayah', $idWilayah);
        }
        $unmatchedMasyarakat = $unmatchedQuery->whereNotIn('id', $matchedMasyarakatIds->unique())->get();

        $wilayahAdmin = ($idWilayah && $user->entitas) ? $user->entitas->wilayah : null;

        return view('survei.validasi.index', compact('rts', 'availablePeriods', 'bulan', 'tahun', 'wilayahAdmin', 'unmatchedMasyarakat'));
    }

    public function showRtDetail($id_sesi, $nama_rt, $rw = null)
    {
        $idWilayah = $this->getUserWilayahId();

        $rtDesaQuery = RtDesa::with(['wilayah', 'sesi'])
            ->where('id_sesi', $id_sesi)
            ->where('nama_rt', $nama_rt);

        if ($idWilayah) {
            $rtDesaQuery->where('id_wilayah', $idWilayah);
        }

        if ($rw) {
            $rtDesaQuery->where('rw', $rw);
        }

        $rtDesa = $rtDesaQuery->first();
        if (!$rtDesa) {
            // Jika Ketua RT belum mengisi baseline, tetap izinkan admin melihat data riil warga yang sudah masuk
            $sesiQuery = SesiSurvei::with('wilayah')->where('id', $id_sesi);
            if ($idWilayah) {
                $sesiQuery->where('id_wilayah', $idWilayah);
            }
            $sesi = $sesiQuery->firstOrFail();

            $rtDesa = (object)[
                'id' => null,
                'id_sesi' => $sesi->id,
                'id_wilayah' => $sesi->id_wilayah,
                'nama_rt' => $nama_rt,
                'rw' => $rw,
                'dusun' => null,
                'nama_ketua_rt' => 'Belum Mengisi Baseline',
                'total_kk_baseline' => 0,
                'total_jiwa_baseline' => 0,
                'wilayah' => $sesi->wilayah,
                'sesi' => $sesi,
            ];
        }

        preg_match('/\d+/', $rtDesa->nama_rt, $rtMatch);
        preg_match('/\d+/', $rtDesa->rw, $rwMatch);
        
        $rt_digit = $rtMatch[0] ?? '';
        $rw_digit = $rwMatch[0] ?? '';
        
        $masyarakatQuery = MasyarakatDesa::with(['anggota', 'konsumsi'])
            ->whereHas('konsumsi')
            ->where('id_sesi', $id_sesi)
            ->orderBy('id', 'desc');

        if ($idWilayah) {
            $masyarakatQuery->where('id_wilayah', $idWilayah);
        } elseif ($rtDesa->id_wilayah) {
            $masyarakatQuery->where('id_wilayah', $rtDesa->id_wilayah);
        }
            
        $masyarakatDesaList = $masyarakatQuery->get()
            ->filter(function($m) use ($rt_digit, $rw_digit, $rtDesa) {
                 $m_rt = preg_replace('/\D/', '', (string)$m->rt);
                 $m_rw = preg_replace('/\D/', '', (string)$m->rw);
                 
                 $rt_match = (int)$m_rt === (int)$rt_digit;
                 
                 $rw_match = true;
                 if (!empty($rtDesa->rw) && $rw_digit !== '') {
                     $rw_match = (int)$m_rw === (int)$rw_digit;
                 }

                 // Pastikan nama desa cocok dengan desa RT
                 $rt_desa_name = trim(str_ireplace('desa ', '', $rtDesa->wilayah->nama ?? ''));
                 $m_desa_name = trim(str_ireplace('desa ', '', $m->desa ?? ''));
                 $desa_match = true;
                 if (!empty($rt_desa_name) && !empty($m_desa_name)) {
                     $desa_match = (strtolower($m_desa_name) === strtolower($rt_desa_name));
                 }
                 
                 return $rt_match && $rw_match && $desa_match;
            });

        return view('survei.validasi.show_rt', compact('rtDesa', 'masyarakatDesaList'));
    }

    public function destroyMasyarakat($id)
    {
        $idWilayah = $this->getUserWilayahId();

        $query = MasyarakatDesa::where('id', $id);
        if ($idWilayah) {
            $query->where('id_wilayah', $idWilayah);
        }

        $masyarakat = $query->firstOrFail();

        $nama = $masyarakat->nama_kepala_keluarga;
        $masyarakat->anggota()->delete();
        $masyarakat->konsumsi()->delete();
        $masyarakat->delete();

        return back()->with('success', 'Data responden ' . $nama . ' berhasil dihapus.');
    }
}
