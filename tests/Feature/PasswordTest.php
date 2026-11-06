<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    public function test_every_role_can_change_password_with_their_current_password(): void
    {
        foreach ([User::PERAN_ADMIN, User::PERAN_CALON_SANTRI, User::PERAN_SANTRI] as $peran) {
            $akun = User::create([
                'nama_user' => 'ubah_password_'.$peran.'_'.uniqid(),
                'password' => 'PasswordLama123',
                'peran' => $peran,
            ]);

            $this->actingAs($akun)->get('/akun/password')
                ->assertOk()
                ->assertSee('Ubah Password');

            $this->post('/akun/password', [
                'password_lama' => 'PasswordLama123',
                'password_baru' => 'PasswordBaru456',
                'password_baru_confirmation' => 'PasswordBaru456',
            ])->assertRedirect('/akun/password')->assertSessionHas('sukses');

            $this->assertTrue(Hash::check('PasswordBaru456', $akun->fresh()->password));
            Auth::logout();
        }
    }

    public function test_wrong_old_password_is_rejected_without_changing_the_password(): void
    {
        $akun = $this->buatAkunSantri();

        $this->actingAs($akun)->from('/akun/password')->post('/akun/password', [
            'password_lama' => 'PasswordSalah123',
            'password_baru' => 'PasswordBaru456',
            'password_baru_confirmation' => 'PasswordBaru456',
        ])->assertRedirect('/akun/password')->assertSessionHasErrors('password_lama');

        $this->assertTrue(Hash::check('PasswordLama123', $akun->fresh()->password));
    }

    public function test_short_new_password_is_rejected(): void
    {
        $akun = $this->buatAkunSantri();

        $this->actingAs($akun)->from('/akun/password')->post('/akun/password', [
            'password_lama' => 'PasswordLama123',
            'password_baru' => 'pendek',
            'password_baru_confirmation' => 'pendek',
        ])->assertRedirect('/akun/password')->assertSessionHasErrors('password_baru');
    }

    public function test_mismatched_new_password_confirmation_is_rejected(): void
    {
        $akun = $this->buatAkunSantri();

        $this->actingAs($akun)->from('/akun/password')->post('/akun/password', [
            'password_lama' => 'PasswordLama123',
            'password_baru' => 'PasswordBaru456',
            'password_baru_confirmation' => 'PasswordLain789',
        ])->assertRedirect('/akun/password')->assertSessionHasErrors('password_baru');
    }

    public function test_guest_is_redirected_to_login_from_password_page(): void
    {
        $this->get('/akun/password')->assertRedirect('/login');
    }

    private function buatAkunSantri(): User
    {
        return User::create([
            'nama_user' => 'ubah_password_gagal_'.uniqid(),
            'password' => 'PasswordLama123',
            'peran' => User::PERAN_SANTRI,
        ]);
    }
}
