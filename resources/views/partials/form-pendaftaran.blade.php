<fieldset class="grid gap-5 rounded-sm border border-garis p-5 sm:grid-cols-2">
    <legend class="px-2 font-serif text-xl font-bold text-primer">Data Calon Santri</legend>

    <x-input
        name="nama_lengkap"
        label="Nama Lengkap Calon Santri"
        type="text"
        :value="$pendaftar?->nama_lengkap"
        maxlength="100"
        required
    />

    <x-input
        name="tanggal_lahir"
        label="Tanggal Lahir"
        type="date"
        :value="$pendaftar?->tanggal_lahir?->format('Y-m-d')"
        required
    />

    <x-textarea
        name="alamat"
        label="Alamat Lengkap Tempat Tinggal"
        :value="$pendaftar?->alamat"
        rows="4"
        cols="50"
        class="sm:col-span-2"
        required
    />
</fieldset>

<fieldset class="grid gap-5 rounded-sm border border-garis p-5 sm:grid-cols-2">
    <legend class="px-2 font-serif text-xl font-bold text-primer">Data Orang Tua / Wali</legend>

    <x-input
        name="nama_wali"
        label="Nama Orang Tua / Wali"
        type="text"
        :value="$pendaftar?->nama_wali"
        maxlength="100"
        required
    />

    <div>
        <x-input
            name="no_telepon"
            label="Nomor Telepon (WhatsApp)"
            type="text"
            :value="$pendaftar?->no_telepon"
            maxlength="16"
            placeholder="Contoh: 081234567890 atau +6281234567890"
            required
        />
        <p class="mt-1 text-sm text-teks/75">Format: 10–15 digit angka, diawali dengan 0 atau +62.</p>
    </div>
</fieldset>
