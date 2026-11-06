<?php

namespace Tests\Feature;

use App\Models\PeriodePendaftaran;
use App\Services\PeriodeService;
use Carbon\Carbon;
use Tests\TestCase;

class PeriodeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 10:00:00', 'Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_available_scope_includes_first_and_last_date_but_excludes_day_before(): void
    {
        $periodeHariIni = $this->buatPeriode('Periode Mulai Hari Ini', '2026-11-01', '2026-11-05');
        $periodeBerakhirHariIni = $this->buatPeriode('Periode Akhir Hari Ini', '2026-10-28', '2026-11-01');
        $periodeBesok = $this->buatPeriode('Periode Mulai Besok', '2026-11-02', '2026-11-05');

        $tersedia = PeriodePendaftaran::query()->tersedia()->pluck('id_periode')->all();

        $this->assertContains($periodeHariIni->id_periode, $tersedia);
        $this->assertContains($periodeBerakhirHariIni->id_periode, $tersedia);
        $this->assertNotContains($periodeBesok->id_periode, $tersedia);
        $this->assertFalse($periodeBesok->tersedia());
    }

    public function test_sync_closes_open_periods_after_their_end_date_and_preserves_scheduled_periods(): void
    {
        $periodeLewat = $this->buatPeriode('Periode Lewat', '2026-10-01', '2026-10-31');
        $periodeAktif = $this->buatPeriode('Periode Aktif', '2026-11-01', '2026-11-05');
        $periodeTerjadwal = $this->buatPeriode('Periode Terjadwal', '2026-11-02', '2026-11-05');
        $jumlahPeriodeKedaluwarsa = PeriodePendaftaran::query()
            ->where('status_periode', PeriodePendaftaran::STATUS_DIBUKA)
            ->whereDate('tanggal_tutup', '<', '2026-11-01')
            ->count();

        $jumlahDitutup = app(PeriodeService::class)->sinkronkan();

        $this->assertSame($jumlahPeriodeKedaluwarsa, $jumlahDitutup);
        $this->assertSame(PeriodePendaftaran::STATUS_DITUTUP, $periodeLewat->fresh()->status_periode);
        $this->assertSame(PeriodePendaftaran::STATUS_DIBUKA, $periodeAktif->fresh()->status_periode);
        $this->assertSame(PeriodePendaftaran::STATUS_DIBUKA, $periodeTerjadwal->fresh()->status_periode);
        $this->assertSame('Dijadwalkan', $periodeTerjadwal->fresh()->labelStatus());
    }

    public function test_expired_open_period_is_closed_at_the_start_of_a_period_read_request(): void
    {
        $periodeLewat = $this->buatPeriode('Periode Ditutup Saat Request', '2026-10-01', '2026-10-31');

        $this->get('/')->assertOk();

        $this->assertSame(PeriodePendaftaran::STATUS_DITUTUP, $periodeLewat->fresh()->status_periode);
        $this->assertFalse($periodeLewat->fresh()->tersedia());
    }

    public function test_expired_open_period_is_closed_by_artisan_command(): void
    {
        $periodeLewat = $this->buatPeriode('Periode Ditutup Command', '2026-10-01', '2026-10-31');

        $this->artisan('periode:sinkron')->assertExitCode(0);

        $this->assertSame(PeriodePendaftaran::STATUS_DITUTUP, $periodeLewat->fresh()->status_periode);
    }

    public function test_scheduled_period_becomes_available_when_opening_date_arrives(): void
    {
        $periodeTerjadwal = $this->buatPeriode('Periode Mulai Besok', '2026-11-02', '2026-11-05');

        $this->assertFalse($periodeTerjadwal->fresh()->tersedia());
        $this->assertSame('Dijadwalkan', $periodeTerjadwal->fresh()->labelStatus());

        Carbon::setTestNow('2026-11-02 00:01:00');
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Pendaftaran dibuka sampai 05 November 2026');
        $this->assertSame($periodeTerjadwal->id_periode, $response->viewData('periodeAktif')->id_periode);
        $this->assertSame(PeriodePendaftaran::STATUS_DIBUKA, $periodeTerjadwal->fresh()->status_periode);
        $this->assertTrue($periodeTerjadwal->fresh()->tersedia());
    }

    public function test_homepage_announces_scheduled_opening_date_before_it_arrives(): void
    {
        $this->buatPeriode('Periode Akan Dibuka', '2026-11-02', '2026-11-05');

        $this->get('/')
            ->assertOk()
            ->assertSee('Pendaftaran akan dibuka pada 02 November 2026');
    }

    private function buatPeriode(string $nama, string $tanggalBuka, string $tanggalTutup): PeriodePendaftaran
    {
        return PeriodePendaftaran::create([
            'nama_periode' => $nama.'_'.uniqid(),
            'tanggal_buka' => $tanggalBuka,
            'tanggal_tutup' => $tanggalTutup,
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);
    }
}
