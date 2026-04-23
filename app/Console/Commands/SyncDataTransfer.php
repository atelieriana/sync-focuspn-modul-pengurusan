<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncDataTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:transfer {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi data transfer BKPN';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Sync Transfer BKPN
        $this->call('sync:transfer-bkpn',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Transfer BKPN Detail
        $this->call('sync:transfer-bkpn-detail',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);
    }
}
