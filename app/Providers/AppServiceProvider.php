<?php

namespace App\Providers;

use App\Models\InformasiPondok;
use App\Models\PeriodePendaftaran;
use App\Services\PemulihanAkun;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('welcome', function (ViewInstance $view): void {
            $view->with('informasiList', InformasiPondok::query()
                ->orderByDesc('diperbarui')
                ->orderByDesc('id_informasi')
                ->get());

            $view->with('periodeAktif', PeriodePendaftaran::query()
                ->tersedia()
                ->orderByDesc('tanggal_buka')
                ->orderByDesc('id_periode')
                ->first());
            $view->with('periodeTerjadwal', PeriodePendaftaran::query()
                ->where('status_periode', PeriodePendaftaran::STATUS_DIBUKA)
                ->whereDate('tanggal_buka', '>', today('Asia/Jakarta')->toDateString())
                ->orderBy('tanggal_buka')
                ->orderBy('id_periode')
                ->first());
        });

        View::composer('components.layout.admin', function (ViewInstance $view): void {
            $view->with('jumlahPemulihanMenunggu', app(PemulihanAkun::class)->jumlahMenunggu());
        });
    }
}
