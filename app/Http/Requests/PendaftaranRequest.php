<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PendaftaranRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna memiliki otorisasi untuk membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk formulir pendaftaran.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'alamat' => ['required', 'string'],
            'nama_wali' => ['required', 'string', 'max:100'],
            'no_telepon' => ['required', 'string', 'regex:/^(\+62[0-9]{8,13}|0[0-9]{9,14})$/'],
        ];
    }

    /**
     * Dapatkan pesan kesalahan khusus untuk validator dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap calon santri wajib diisi.',
            'nama_lengkap.max' => 'Nama lengkap calon santri maksimal 100 karakter.',
            'tanggal_lahir.required' => 'Tanggal lahir calon santri wajib diisi.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'alamat.required' => 'Alamat lengkap tempat tinggal wajib diisi.',
            'nama_wali.required' => 'Nama wali calon santri wajib diisi.',
            'nama_wali.max' => 'Nama wali calon santri maksimal 100 karakter.',
            'no_telepon.required' => 'Nomor telepon wajib diisi.',
            'no_telepon.regex' => 'Nomor telepon wajib berupa angka (10–15 digit) dan diawali dengan 0 atau +62.',
        ];
    }
}
