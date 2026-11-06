@if (session('sukses'))
    <section aria-label="Pesan Sukses">
        <p><strong>Sukses:</strong> {{ session('sukses') }}</p>
    </section>
@endif

@if (session('info'))
    <section aria-label="Pesan Informasi">
        <p><strong>Informasi:</strong> {{ session('info') }}</p>
    </section>
@endif

@if (session('galat') || session('error'))
    <section aria-label="Pesan Galat">
        <p><strong>Galat:</strong> {{ session('galat') ?? session('error') }}</p>
    </section>
@endif

@if ($errors->any())
    <section aria-label="Daftar Kesalahan Validasi">
        <p><strong>Terjadi kesalahan input:</strong></p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </section>
@endif
