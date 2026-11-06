<?php

namespace App\Http\Requests;

use App\Models\PeriodePendaftaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PeriodePendaftaranRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki otorisasi untuk membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk permintaan ini.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $aturanTanggalBuka = ['required', 'date'];

        if ($this->routeIs('admin.periode.store')) {
            $aturanTanggalBuka[] = 'after_or_equal:'.today('Asia/Jakarta')->toDateString();
        }

        return [
            'nama_periode' => ['required', 'string', 'max:50'],
            'tanggal_buka' => $aturanTanggalBuka,
            'tanggal_tutup' => ['required', 'date', 'after_or_equal:tanggal_buka'],
            'status_periode' => ['nullable', 'string', 'in:'.PeriodePendaftaran::STATUS_DIBUKA.','.PeriodePendaftaran::STATUS_DITUTUP],
        ];
    }

    /**
     * Dapatkan pesan kesalahan khusus untuk validator.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_periode.required' => 'Nama periode pendaftaran wajib diisi.',
            'nama_periode.max' => 'Nama periode pendaftaran maksimal 50 karakter.',
            'tanggal_buka.required' => 'Tanggal buka pendaftaran wajib diisi.',
            'tanggal_buka.date' => 'Format tanggal buka pendaftaran tidak valid.',
            'tanggal_buka.after_or_equal' => 'Tanggal buka pendaftaran tidak boleh sudah terlewat.',
            'tanggal_tutup.required' => 'Tanggal tutup pendaftaran wajib diisi.',
            'tanggal_tutup.date' => 'Format tanggal tutup pendaftaran tidak valid.',
            'tanggal_tutup.after_or_equal' => 'Tanggal tutup pendaftaran harus sama atau setelah tanggal buka.',
            'status_periode.in' => 'Status periode harus dipilih antara dibuka atau ditutup.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['tanggal_buka', 'tanggal_tutup'])) {
                return;
            }

            $query = PeriodePendaftaran::query()
                ->whereDate('tanggal_buka', '<=', $this->input('tanggal_tutup'))
                ->whereDate('tanggal_tutup', '>=', $this->input('tanggal_buka'));
            $periode = $this->route('periode');

            if ($periode instanceof PeriodePendaftaran) {
                $query->where('id_periode', '!=', $periode->id_periode);
            }

            if ($query->exists()) {
                $validator->errors()->add('tanggal_buka', 'Rentang tanggal bertumpang tindih dengan periode pendaftaran lain.');
            }
        }];
    }
}
