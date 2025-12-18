<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransDebiturBadanHukumRepository;
use App\Repositories\FocusPN\TGlobalRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransDebiturBadanHukumRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransDebiturBadanHukum extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 100;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransDebiturBadanHukumRepository $migrasiTransDebiturBadanHukumRepository;
    private TransDebiturBadanHukumRepository $transDebiturBadanHukumRepository;
    private Collection $listDebiturBadanHukumFocusPN;
    private Collection $listDebiturBadanHukumModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingDebiturBadanHukumFocusPN;
    private array $remappingDebiturBadanHukumModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransDebiturBadanHukumRepository = new MigrasiTransDebiturBadanHukumRepository();
        $this->transDebiturBadanHukumRepository = new TransDebiturBadanHukumRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-debitur-badan-hukum {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi trans debitur badan hukum';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi debitur badan hukum dimulai!');
        $this->setSatuanKerja()
            ->getListTransDebiturBadanHukumFocusPN()
            ->mappingTransDebiturBadanHukumFocusPN()
            ->doSync()
            ->getListTransDebiturBadanHukumModulPengurusan()
            ->mappingTransDebiturBadanHukumModulPengurusan()
            ->doResyncTransDebiturBadanHukum();
        $this->info('Sinkronisasi transaksi debitur badan hukum selesai');
    }

    /**
     * Digunakan untuk melakukan set kode satuan kerja yang akan dilakukan sinkronisasi
     *
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

    /**
     * Digunakan untuk mengambil data migrasi debitur BadanHukum focuspn
     *
     * @return $this
     */
    private function getListTransDebiturBadanHukumFocusPN()
    {
        $this->listDebiturBadanHukumFocusPN = $this->migrasiTransDebiturBadanHukumRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk mapping trans debitur focus pn
     *
     * @return $this
     */
    private function mappingTransDebiturBadanHukumFocusPN()
    {
        $this->remappingDebiturBadanHukumFocusPN = array_map(function ($transDebiturBadanHukum) {
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transDebiturBadanHukum['ID_FOCUSPN'],
                'ID_TRANS_DEBITUR' => $transDebiturBadanHukum['ID_TRANS_DEBITUR'],
                'NPWP_BADAN_HUKUM' => $transDebiturBadanHukum['NPWP'],
                'CREATED_BY' => $transDebiturBadanHukum['CREATED_BY'],
                'CREATED_AT' => $transDebiturBadanHukum['CREATED_AT'],
                'UPDATED_BY' => $transDebiturBadanHukum['UPDATED_BY'],
                'UPDATED_AT' => $transDebiturBadanHukum['UPDATED_AT']
            ];
        }, $this->listDebiturBadanHukumFocusPN->toArray());

        return $this;
    }

    /**
     * Digunakan untuk melakukan sinkronisasi data
     *
     * @return static
     */
    private function doSync()
    {
        $this->deleteOldData()
            ->saveNewData();

        return $this;
    }

    /**
     * Digunakan untuk menghapus data lama
     *
     * @return $this
     */
    private function deleteOldData()
    {
        $this->transDebiturBadanHukumRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk menyimpan data baru
     *
     * @return void
     */
    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_GLOBAL ke TRANS_DEBITUR_BADAN_HUKUM: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingDebiturBadanHukumFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transDebiturBadanHukum = new TransDebiturBadanHukumRepository();
                $transDebiturBadanHukum->insert($chunk->toArray());
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

    /**
     * Digunakan untuk mendapoatkan trans debitur BadanHukum dari modul pengurusan berdasarkan id satuan kerja kpknl
     *
     * @return $this
     */
    private function getListTransDebiturBadanHukumModulPengurusan()
    {
        $this->listDebiturBadanHukumModulPengurusan = $this->transDebiturBadanHukumRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk melakukan mapping trans debitur BadanHukum
     * @return $this
     */
    private function mappingTransDebiturBadanHukumModulPengurusan()
    {
        $this->remappingDebiturBadanHukumModulPengurusan = array_map(function ($transDebiturBadanHukum) {
            return [
                'ID' => $transDebiturBadanHukum['ID_FOCUSPN'],
                'ID_TRANS_DEBITUR_BADAN_HUKUM' => $transDebiturBadanHukum['ID']
            ];
        }, $this->listDebiturBadanHukumModulPengurusan->toArray());

        return $this;
    }

    private function doResyncTransDebiturBadanHukum()
    {
        $this->info('Tahapan sinkonrisasi TRANS_DEBITUR_BADAN_HUKUM ke T_GLOBAL: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingDebiturBadanHukumModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tGlobal = new TGlobalRepository();
                $tGlobal->upsert($chunk->toArray(),['ID'],['ID_TRANS_DEBITUR_BADAN_HUKUM']);
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
