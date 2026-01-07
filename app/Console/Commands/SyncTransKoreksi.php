<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransKoreksiRepository;
use App\Repositories\FocusPN\TKoreksiRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransKoreksiRepository;
use Exception;
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
        $this->info('Sinkronisasi transaksi koreksi dimulai!');
        $this->setSatuanKerja()
            ->getListTransKoreksiFocusPN()
            ->mappingListTransKoreksiFocusPN()
            ->doSync()
            ->getListTransKoreksiModulPengurusan()
            ->mappingListTransaksiKoreksiModulPengurusan()
            ->doResyncTransKoreksiModulPengurusan();
        $this->info('Sinkronisasi transaksi koreksi selesai');
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
                'ID_TAHAP_PENGURUSAN' => $transKoreksi['ID_TAHAP_PENGURUSAN'],
                'ID_REF_MATA_UANG' => $transKoreksi['ID_REF_MATA_UANG'],
                'ID_REF_BIAD' => $transKoreksi['ID_REF_BIAD'],
                'ID_FOCUSPN' => $transKoreksi['ID_FOCUSPN'],
                'KOREKSI_POKOK' => $transKoreksi['KOREKSI_POKOK'],
                'KOREKSI_BUNGA' => $transKoreksi['KOREKSI_BUNGA'],
                'KOREKSI_DENDA' => $transKoreksi['KOREKSI_DENDA'],
                'KOREKSI_LAINNYA' => $transKoreksi['KOREKSI_LAINNYA'],
                'CREATED_BY' => $transKoreksi['CREATED_BY'],
                'CREATED_AT' => $transKoreksi['CREATED_AT'],
                'UPDATED_BY' => $transKoreksi['UPDATED_BY'],
                'UPDATED_AT' => $transKoreksi['UPDATED_AT'],
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
        $this->transKoreksiRepository->deleteByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_KOREKSI ke TRANS_KOREKSI: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransKoreksiFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transKoreksi = new TransKoreksiRepository();
                $transKoreksi->insert($chunk->toArray());
                $progressBar->advance();
            }

            $this->database::commit();
            $progressBar->finish();
            $this->output->newLine();
        }
        catch (Exception $exception)
        {
            $this->database::rollBack();
            $this->error($exception->getMessage());
        }
    }

    private function getListTransKoreksiModulPengurusan()
    {
        $this->listTransKoreksiModulPengurusan = $this->transKoreksiRepository->getByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransaksiKoreksiModulPengurusan()
    {
        $this->remappingListTransKoreksiModulPengurusan = array_map(function ($transKoreksi){
            return [
                'ID' => $transKoreksi['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transKoreksi['ID']
            ];
        }, $this->listTransKoreksiModulPengurusan->toArray());
        return $this;
    }

    private function doResyncTransKoreksiModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_KOREKSI ke T_KOREKSI: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransKoreksiModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tKoreksi = new TKoreksiRepository();
                $tKoreksi->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
                $progressBar->advance();
            }

            $this->database::commit();
            $progressBar->finish();
            $this->output->newLine();
        }
        catch (Exception $exception)
        {
            $this->database::rollBack();
            $this->error($exception->getMessage());
        }
    }
}
