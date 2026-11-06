<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TagihanSyahriyahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nominal' => ['required', 'integer', 'min:1000'],
            'sasaran' => ['required', 'in:semua,satu'],
            'id_santri' => ['nullable', 'required_if:sasaran,satu', 'integer', 'exists:santri,id_santri'],
            'paksa' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nominal.required' => 'Nominal tagihan wajib diisi.',
            'nominal.integer' => 'Nominal harus berupa bilangan bulat rupiah.',
            'nominal.min' => 'Nominal tagihan minimal Rp 1.000.',
            'sasaran.required' => 'Sasaran pembuatan tagihan wajib dipilih.',
            'sasaran.in' => 'Sasaran pembuatan tagihan tidak valid.',
            'id_santri.required_if' => 'Pilih santri yang akan dibuatkan tagihan.',
            'id_santri.exists' => 'Santri yang dipilih tidak ditemukan.',
        ];
    }
}
