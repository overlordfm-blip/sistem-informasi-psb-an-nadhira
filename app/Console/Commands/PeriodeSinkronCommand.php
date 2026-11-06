<?php

namespace App\Console\Commands;

use App\Services\PeriodeService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('periode:sinkron')]
#[Description('Menutup otomatis periode pendaftaran yang telah melewati tanggal tutup')]
class PeriodeSinkronCommand extends Command
{
    public function handle(PeriodeService $periodeService): int
    {
        $jumlahDitutup = $periodeService->sinkronkan();
        $this->info("{$jumlahDitutup} periode pendaftaran ditutup otomatis.");

        return self::SUCCESS;
    }
}
