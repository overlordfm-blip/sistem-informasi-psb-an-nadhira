<?php

namespace App\Console\Commands;

use App\Services\PemulihanAkun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pemulihan:bersihkan')]
#[Description('Membersihkan permintaan pemulihan akun yang kedaluwarsa atau selesai')]
class PemulihanBersihkanCommand extends Command
{
    public function handle(PemulihanAkun $pemulihanAkun): int
    {
        $jumlahDihapus = $pemulihanAkun->bersihkan();
        $this->info("{$jumlahDihapus} berkas permintaan pemulihan dibersihkan.");

        return self::SUCCESS;
    }
}
