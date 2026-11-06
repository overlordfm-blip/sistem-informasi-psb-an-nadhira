@if (auth()->user()->isAdmin())
    <x-layout.admin title="Ubah Password">
        @include('akun.partials.form-password')
    </x-layout.admin>
@else
    <x-layout.pengguna title="Ubah Password">
        @include('akun.partials.form-password')
    </x-layout.pengguna>
@endif