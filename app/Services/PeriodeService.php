<?php

namespace App\Services;

use App\Models\PeriodePendaftaran;

class PeriodeService
{
    public function sinkronkan(): int
    {
        return PeriodePendaftaran::query()
            ->where('status_periode', PeriodePendaftaran::STATUS_DIBUKA)
            ->whereDate('tanggal_tutup', '<', now('Asia/Jakarta')->toDateString())
            ->update(['status_periode' => PeriodePendaftaran::STATUS_DITUTUP]);
    }
}
