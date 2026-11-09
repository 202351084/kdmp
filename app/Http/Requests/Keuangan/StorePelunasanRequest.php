<?php

namespace App\Http\Requests\Keuangan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StorePelunasanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $idEntitas = auth()->user()->id_entitas
            ?? (app()->bound('entitas_aktif') ? app('entitas_aktif') : null)
            ?? (app()->bound('koperasi_aktif') ? app('koperasi_aktif') : null);
        $kasBank = $this->input('id_kas_bank')
            ? DB::table('master_kas_bank')
                ->where('id_kas_bank', $this->input('id_kas_bank'))
                ->where('id_entitas', $idEntitas)
                ->where('is_active', true)
                ->first()
            : null;
        $perluDataTransfer = $this->input('jenis') === 'terima_piutang'
            && $kasBank?->jenis === 'bank';
        $kasBankRule = Rule::exists('master_kas_bank', 'id_kas_bank')
            ->where('id_entitas', $idEntitas)
            ->where('is_active', true);
        if ($perluDataTransfer) {
            $kasBankRule->whereNotNull('no_rekening')->where('no_rekening', '<>', '');
        }

        return [
            'jenis'                  => ['required', Rule::in(['terima_piutang', 'bayar_hutang'])],
            'id_pihak'               => ['required', 'exists:master_pihak,id_pihak'],
            'tanggal'                => ['required', 'date'],
            'id_kas_bank'            => [
                'required',
                'integer',
                $kasBankRule,
            ],
            'transfer_pengirim_nama' => [Rule::requiredIf($perluDataTransfer), 'nullable', 'string', 'max:150'],
            'transfer_pengirim_bank' => [Rule::requiredIf($perluDataTransfer), 'nullable', 'string', 'max:100'],
            'transfer_pengirim_rekening' => [Rule::requiredIf($perluDataTransfer), 'nullable', 'string', 'max:50'],
            'transfer_no_referensi' => [Rule::requiredIf($perluDataTransfer), 'nullable', 'string', 'max:100'],
            'transfer_dikonfirmasi' => [Rule::requiredIf($perluDataTransfer), 'accepted'],
            'catatan'                => ['nullable', 'string', 'max:500'],

            // Detail wajib ada minimal 1 baris
            'detail'                 => ['required', 'array', 'min:1'],
            'detail.*.nilai_bayar'   => ['required', 'numeric', 'min:0.01'],

            // Salah satu dari keduanya wajib ada, tergantung jenis
            'detail.*.id_piutang'   => ['nullable', 'integer'],
            'detail.*.id_hutang'    => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'detail.required'              => 'Pilih minimal satu piutang/hutang yang akan dilunasi.',
            'detail.min'                   => 'Pilih minimal satu piutang/hutang yang akan dilunasi.',
            'detail.*.nilai_bayar.min'     => 'Nilai bayar harus lebih dari 0.',
            'detail.*.nilai_bayar.required'=> 'Nilai bayar wajib diisi untuk setiap baris.',
            'transfer_pengirim_nama.required' => 'Nama pengirim wajib diisi untuk penerimaan melalui bank.',
            'transfer_pengirim_bank.required' => 'Bank pengirim wajib diisi untuk penerimaan melalui bank.',
            'transfer_pengirim_rekening.required' => 'Nomor rekening pengirim wajib diisi untuk penerimaan melalui bank.',
            'transfer_no_referensi.required' => 'Nomor referensi transfer wajib diisi untuk penerimaan melalui bank.',
            'transfer_dikonfirmasi.accepted' => 'Konfirmasi bahwa dana sudah diterima wajib dicentang.',
        ];
    }
}
