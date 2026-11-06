<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class RegistrasiRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $key = 'registrasi:percobaan:'.hash('sha256', (string) $this->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $detik = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'nama_user' => "Batas 5 percobaan registrasi per jam tercapai. Coba lagi dalam {$detik} detik.",
            ]);
        }

        RateLimiter::hit($key, 3600);
    }

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_user' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._]+$/', 'unique:users,nama_user'],
            'password' => ['required', 'string', 'min:8'],
            'captcha_answer' => ['required', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('website')) {
                $validator->errors()->add('website', 'Permintaan registrasi tidak valid.');
            }

            $captchaExpected = $this->session()->pull('registrasi.captcha_jawaban');
            if ($captchaExpected === null || (string) $this->input('captcha_answer') !== (string) $captchaExpected) {
                $validator->errors()->add('captcha_answer', 'Jawaban penjumlahan tidak benar.');
            }

            $startedAt = filter_var($this->input('form_started_at'), FILTER_VALIDATE_INT);
            $nonce = (string) $this->input('form_nonce', '');
            $signature = (string) $this->input('form_signature', '');
            $nonceSesi = (string) $this->session()->pull('registrasi.form_nonce', '');
            $umurForm = $startedAt === false ? null : now()->timestamp - $startedAt;
            $signatureBenar = $startedAt !== false
                && hash_equals(
                    hash_hmac('sha256', $startedAt.'|'.$nonce, (string) config('app.key')),
                    $signature
                );

            if (
                ! $signatureBenar
                || $nonce === ''
                || $nonceSesi === ''
                || ! hash_equals($nonceSesi, $nonce)
                || $umurForm === null
                || $umurForm < 3
                || $umurForm > 900
            ) {
                $validator->errors()->add('form_token', 'Formulir kedaluwarsa atau dikirim terlalu cepat. Muat ulang formulir lalu coba lagi.');
            }
        });
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
            'captcha_answer.required' => 'Jawaban penjumlahan wajib diisi.',
            'captcha_answer.integer' => 'Jawaban penjumlahan harus berupa angka.',
        ];
    }
}
