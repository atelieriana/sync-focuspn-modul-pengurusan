<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncDataRef extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:referensi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk menjalankan seluruh tahapan migrasi referensi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Migrasi referensi dimulai!');

        // Sync Ref Rohaniwan
        $this->call('sync:ref-rohaniwan');

        // Sync Ref Kurs
        $this->call('sync:ref-kurs');

        // Sync Ref Peraturan
        $this->call('sync:ref-peraturan');

        // Sync Ref Saksi
        $this->call('sync:ref-saksi');

        $this->info('Migrasi referensi selesai!');
    }
}
