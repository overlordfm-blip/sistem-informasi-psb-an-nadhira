<div class="space-y-5">
    <x-input name="judul" label="Judul Informasi" :value="$informasi->judul ?? ''" maxlength="150" required />
    <x-textarea name="isi" label="Isi Informasi" :value="$informasi->isi ?? ''" rows="8" cols="60" required />
</div>
