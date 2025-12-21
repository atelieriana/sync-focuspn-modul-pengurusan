<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransAngsuranRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransAngsuranRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SyncTransAngsuran extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 500;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransAngsuranRepository $migrasiTransAngsuranRepository;
    private TransAngsuranRepository $transAngsuranRepository;
    private Collection $listTransAngsuranFocusPN;
    private Collection $listTransAngsuranModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingListTransAngsuranFocusPN;
    private array $remappingListTransAngsuranMdoulPengurusan;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-angsuran {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi transaksi angsuran';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
