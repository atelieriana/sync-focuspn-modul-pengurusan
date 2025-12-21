<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransKoreksiRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransKoreksiRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransKoreksi extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 1000;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransKoreksiRepository $migrasiTransKoreksiRepository;
    private TransKoreksiRepository $transKoreksiRepository;
    private Collection $listTransKoreksiFocusPN;
    private Collection $listTransKoreksiModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingListTransKoreksiFocusPN;
    private array $remappingListTransKoreksiModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransKoreksiRepository = new MigrasiTransKoreksiRepository();
        $this->transKoreksiRepository = new TransKoreksiRepository();
    }

    protected $signature = 'sync:trans-koreksi {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi koreksi piutang';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi piutang dimulai!');
        $this->setSatuanKerja()
            ->getListTransKoreksiFocusPN()
            ->mappingListTransKoreksiFocusPN()
            ->doSync()
            ->getListTransKoreksiModulPengurusan()
            ->mappingListTransaksiKoreksiModulPengurusan()
            ->doResyncTransKoreksiModulPengurusan();
        $this->info('Sinkronisasi transaksi piutang selesai');
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

    private function getListTransKoreksiFocusPN()
    {
        $this->listTransKoreksiFocusPN = $this->migrasiTransKoreksiRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransKoreksiFocusPN()
    {
        $this->remappingListTransKoreksiFocusPN = array_map(function ($transKoreksi){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_TRANS_PIUTANG' => $transKoreksi['ID_TRANS_PIUTANG_MODUL_PENGURUSAN'],
                'ID_TAHAP_PENGURUSAN' => $transKoreksi['ID_TRA']
            ];
        }, $this->listTransKoreksiFocusPN->toArray());

        return $this;
    }

    private function doSync()
    {
        $this->deleteOldData()
            ->saveNewData();
        return $this;
    }

    private function deleteOldData()
    {
        return $this;
    }

    private function saveNewData()
    {

    }

    private function getListTransKoreksiModulPengurusan()
    {
        return $this;
    }

    private function mappingListTransaksiKoreksiModulPengurusan()
    {
        return $this;
    }

    private function doResyncTransKoreksiModulPengurusan()
    {
        return $this;
    }
}
