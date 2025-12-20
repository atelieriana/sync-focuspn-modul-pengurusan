<?php

namespace App\Console\Commands;

use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\BufferedOutput;

class SyncData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:data {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk menjalankan seluruh tahapan migrasi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Migrasi dimulai!');

        // Sync Trans Piutang
        $this->call('sync:trans-piutang',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Debitur
        $this->call('sync:trans-debitur',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Debitur Perseorangan
        $this->call('sync:trans-debitur-perseorangan',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Debitur Badan Hukum
        $this->call('sync:trans-debitur-badan-hukum',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Pengurus Badan Hukum
        $this->call('sync:trans-pengurus-badan-hukum',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Penjamin Hutang Lainnya
        $this->call('sync:trans-penjamin-hutang',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Penjamin Hutang Lainnya
        $this->call('sync:trans-penjamin-hutang',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Tahap Pengurusan
        $this->call('sync:trans-tahap-pengurusan',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        $this->info('Migrasi selesai!');
    }
}
