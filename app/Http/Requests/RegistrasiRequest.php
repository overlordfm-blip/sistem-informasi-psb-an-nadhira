<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrasiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_user' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._]+$/', 'unique:users,nama_user'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_user.required' => 'Nama user wajib diisi.',
            'nama_user.max' => 'Nama user maksimal 50 karakter.',
            'nama_user.regex' => 'Nama user hanya boleh berisi huruf, angka, titik, dan garis bawah.',
            'nama_user.unique' => 'Nama user sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
