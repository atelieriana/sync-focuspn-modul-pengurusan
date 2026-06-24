<?php

namespace App\Console\Commands;

use Exception;
use App\Repositories\FocusPN\MigrasiTransCekalRepository;
use App\Repositories\FocusPN\TCekalRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransCekalRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransCekal extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 500;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransCekalRepository $migrasiTransCekalRepository;
    private TransCekalRepository $transCekalRepository;
    private int $idSatuanKerja;
    private Collection $listTransCekalFocusPN;
    private Collection $listTransCekalModulPengurusan;
    private array $remappingListTransCekalFocusPN;
    private array $remappingListTransCekalModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransCekalRepository = new MigrasiTransCekalRepository();
        $this->transCekalRepository = new TransCekalRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-cekal {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi cekal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mulai proses sinkronisasi transaksi cekal');
        $this->setSatuanKerja()
            ->getListTransCekalFocusPN()
            ->mappingListTransCekalFocusPN()
            ->doSync()
            ->getListTransCekalModulPengurusan()
            ->mappingListTransCekalModulPengurusan()
            ->resyncTransCekalModulPengurusan();
        $this->info('Proses sinkronisasi transaksi cekal selesai');
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
            $this->idSatuanKerja = $satuanKerja->id;
        else
            $this->error('Kode satuan kerja tidak ditemukan');
        return $this;
    }

    private function getListTransCekalFocusPN()
    {
        $this->listTransCekalFocusPN = $this->migrasiTransCekalRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransCekalFocusPN()
    {
        $this->remappingListTransCekalFocusPN = array_map(function($transCekal){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transCekal['ID_FOCUSPN'],
                'id_trans_debitur' => $transCekal['ID_TRANS_DEBITUR'],
                'nomor_kmk' => $transCekal['NOMOR_KMK'],
                'tanggal_kmk' => $transCekal['TANGGAL_KMK'],
                'tanggal_mulai' => $transCekal['TANGGAL_CEKAL_AWAL'],
                'tanggal_berakhir' => $transCekal['TANGGAL_CEKAL_AKHIR'],
                'created_by' => $transCekal['CREATED_BY'],
                'created_at' => $transCekal['CREATED_AT'],
                'updated_by' => $transCekal['UPDATED_BY'],
                'updated_at' => $transCekal['UPDATED_AT']
            ];
        }, $this->listTransCekalFocusPN->toArray());

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
        $this->transCekalRepository->deleteByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_CEKAL ke TRANS_CEKAL: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransCekalFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transCekal = new TransCekalRepository();
                $transCekal ->insert($chunk->toArray());
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

        return $this;
    }

    private function getListTransCekalModulPengurusan()
    {
        $this->listTransCekalModulPengurusan = $this->transCekalRepository->getByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransCekalModulPengurusan()
    {
        $this->remappingListTransCekalModulPengurusan = array_map(function($transCekal){
            return [
                'ID' => $transCekal['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transCekal['id']
            ];
        }, $this->listTransCekalModulPengurusan->toArray());

        return $this;
    }

    private function resyncTransCekalModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS)CEKAL ke T_CEKAL: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransCekalModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tCekal = new TCekalRepository();
                $tCekal->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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

        return $this;
    }
}
