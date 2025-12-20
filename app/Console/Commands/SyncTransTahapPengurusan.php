<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransTahapPengurusanRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransTahapPengurusan extends Command
{
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransTahapPengurusanRepository $migrasiTransTahapPengurusanRepository;
    private Collection $listTransTahapPengurusanFocusPN;
    private Collection $listTransTahapPengurusanModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingListTransTahapPengurusanFocusPN;
    private array $remamppingListTransTahapPengurusanModulPengurusan;
    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransTahapPengurusanRepository = new MigrasiTransTahapPengurusanRepository();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-tahap-pengurusan {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi tahap pengurusan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi tahap penghurusan!');
        $this->setSatuanKerja()
            ->getListTransTahapPengurusanFocusPN()
            ->remappingListTransTahapPengurusanFocusPN();
        $this->info('Sinkronisasi transaksi tahap pengurusan');
    }

    /**
     * Digunakan untuk melakukan set kode satuan kerja yang akan dilakukan sinkronisasi
     * @return $this
     */
    private function setSatuanKerja()
    {
        $kodeSatuanKerja = $this->argument('kode-satuan-kerja');
        $satuanKerja = $this->refSatuanKerjaRepository->getIdSatuanKerjaByKodeSatuanKerja($kodeSatuanKerja);
        if (!is_null($satuanKerja))
            $this->idSatuanKerja = $satuanKerja->ID;
        else
            $this->error('Kode satuan kerja tidak ditemukan');
        return $this;
    }


    private function getListTransTahapPengurusanFocusPN()
    {
        $this->listTransTahapPengurusanFocusPN = $this->migrasiTransTahapPengurusanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function remappingListTransTahapPengurusanFocusPN()
    {
        $this->remappingListTransTahapPengurusanFocusPN = array_map(function ($transTahapPengurusan){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transTahapPengurusan['ID_FOCUSPN'],
                'ID_TRANS_PIUTANG' => $transTahapPengurusan['ID_TRANS_PIUTANG_MODUL_PENGURUSAN']
            ];
        }, $this->listTransTahapPengurusanFocusPN->toArray());
        return $this;
    }
}
