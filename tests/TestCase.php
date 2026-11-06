<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Pengaman: jangan pernah menjalankan tes pada basis data pengembangan asli
        $koneksi = config('database.default');
        $dbName = config("database.connections.{$koneksi}.database");
        if (! is_string($dbName) || ! str_ends_with($dbName, '_test')) {
            throw new \RuntimeException(
                "Tes tidak boleh dijalankan pada basis data pengembangan asli ({$dbName}). ".
                'Ubah DB_DATABASE menjadi basis data yang berakhiran _test (contoh: an_nadhira_hub_test).'
            );
        }

        // Isolasi berkas privat: arahkan root disk privat ke storage/framework/testing
        $testingDisk = storage_path('framework/testing');
        if (! is_dir($testingDisk)) {
            mkdir($testingDisk, 0755, true);
        }
        Storage::fake('local');
        Storage::disk('local')->deleteDirectory('');
    }

    protected function tearDown(): void
    {
        // Bersihkan direktori tes setelah setiap tes
        $testingDisk = storage_path('framework/testing');
        if (is_dir($testingDisk)) {
            foreach (new \DirectoryIterator($testingDisk) as $file) {
                if ($file->isDir() && ! $file->isDot()) {
                    $this->deleteDirectoryRecursive($file->getPathname());
                } elseif ($file->isFile()) {
                    unlink($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    private function deleteDirectoryRecursive(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (new \DirectoryIterator($path) as $file) {
            if ($file->isDir() && ! $file->isDot()) {
                $this->deleteDirectoryRecursive($file->getPathname());
            } elseif ($file->isFile()) {
                unlink($file->getPathname());
            }
        }

        rmdir($path);
    }
}
