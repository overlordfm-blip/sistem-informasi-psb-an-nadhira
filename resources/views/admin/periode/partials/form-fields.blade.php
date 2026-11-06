<div class="grid gap-5 sm:grid-cols-2">
    <x-input name="nama_periode" label="Nama Periode" :value="$periode->nama_periode ?? ''" maxlength="50" required />
    <x-input name="tanggal_buka" label="Tanggal Buka" type="date" :value="isset($periode->tanggal_buka) ? $periode->tanggal_buka->format('Y-m-d') : ''" required />
    <x-input name="tanggal_tutup" label="Tanggal Tutup" type="date" :value="isset($periode->tanggal_tutup) ? $periode->tanggal_tutup->format('Y-m-d') : ''" required />
    <div>
        <label for="status_periode" class="mb-1 block text-base font-semibold">Status Periode</label>
        <select id="status_periode" name="status_periode" class="min-h-11 w-full rounded-sm border border-garis bg-permukaan px-3 py-2 text-base text-teks focus:border-primer focus:outline-2 focus:outline-primer">
        <option value="ditutup" {{ old('status_periode', $periode->status_periode ?? 'ditutup') === 'ditutup' ? 'selected' : '' }}>
            Ditutup
        </option>
        <option value="dibuka" {{ old('status_periode', $periode->status_periode ?? '') === 'dibuka' ? 'selected' : '' }}>
            Dibuka
        </option>
        </select>
        <p class="mt-1 text-sm text-teks/75">Membuka satu periode akan otomatis menutup periode lain yang sedang dibuka.</p>
        @error('status_periode')
            <p class="mt-1 text-base text-bahaya">{{ $message }}</p>
        @enderror
    </div>
</div>
