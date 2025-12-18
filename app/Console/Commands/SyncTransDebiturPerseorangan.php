<?php

namespace App\Console\Commands;

use App\Models\ModulPengurusan\TransDebiturPerseorangan;
use App\Repositories\FocusPN\MigrasiTransDebiturPerseoranganRepository;
use App\Repositories\FocusPN\TGlobalRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransDebiturPerseoranganRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransDebiturPerseorangan extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 100;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransDebiturPerseoranganRepository $migrasiTransDebiturPerseorangan;
    private TransDebiturPerseoranganRepository $transDebiturPerseoranganRepository;
    private Collection $listDebiturPerseoranganFocusPN;
    private Collection $listDebiturPerseoranganModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingDebiturPerseoranganFocusPN;
    private array $remappingDebiturPerseoranganModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransDebiturPerseorangan = new MigrasiTransDebiturPerseoranganRepository();
        $this->transDebiturPerseoranganRepository = new TransDebiturPerseoranganRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-debitur-perseorangan {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi trans debitur perseorangan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi debitur perseorangan dimulai!');
        $this->setSatuanKerja()
            ->getListTransDebiturPerseoranganFocusPN()
            ->mappingTransDebiturPerseoranganFocusPN()
            ->doSync()
            ->getListTransDebiturPerseoranganModulPengurusan()
            ->mappingTransDebiturPerseoranganModulPengurusan()
            ->doResyncTransDebiturPerseorangan();
        $this->info('Sinkronisasi transaksi debitur perseorangan selesai');
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
     * Digunakan untuk mengambil data migrasi debitur perseorangan focuspn
     *
     * @return $this
     */
    private function getListTransDebiturPerseoranganFocusPN()
    {
        $this->listDebiturPerseoranganFocusPN = $this->migrasiTransDebiturPerseorangan->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk mapping trans debitur focus pn
     *
     * @return $this
     */
    private function mappingTransDebiturPerseoranganFocusPN()
    {
        $this->remappingDebiturPerseoranganFocusPN = array_map(function ($transDebiturPerseorangan) {
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transDebiturPerseorangan['ID_FOCUSPN'],
                'ID_TRANS_DEBITUR' => $transDebiturPerseorangan['ID_TRANS_DEBITUR'],
                'KTP' => $transDebiturPerseorangan['KTP'],
                'NPWP' => $transDebiturPerseorangan['NPWP'],
                'PASPOR' => $transDebiturPerseorangan['PASPOR'],
                'CREATED_BY' => $transDebiturPerseorangan['CREATED_BY'],
                'CREATED_AT' => $transDebiturPerseorangan['CREATED_AT'],
                'UPDATED_BY' => $transDebiturPerseorangan['UPDATED_BY'],
                'UPDATED_AT' => $transDebiturPerseorangan['UPDATED_AT']
            ];
        }, $this->listDebiturPerseoranganFocusPN->toArray());

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
        $this->transDebiturPerseoranganRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk menyimpan data baru
     *
     * @return void
     */
    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_GLOBAL ke TRANS_DEBITUR_PERSEORANGAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingDebiturPerseoranganFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transDebiturPerseorangan = new TransDebiturPerseoranganRepository();
                $transDebiturPerseorangan->insert($chunk->toArray());
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
     * Digunakan untuk mendapoatkan trans debitur perseorangan dari modul pengurusan berdasarkan id satuan kerja kpknl
     *
     * @return $this
     */
    private function getListTransDebiturPerseoranganModulPengurusan()
    {
        $this->listDebiturPerseoranganModulPengurusan = $this->transDebiturPerseoranganRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk melakukan mapping trans debitur perseorangan
     * @return $this
     */
    private function mappingTransDebiturPerseoranganModulPengurusan()
    {
        $this->remappingDebiturPerseoranganModulPengurusan = array_map(function ($transDebiturPerseorangan) {
            return [
                'ID' => $transDebiturPerseorangan['ID_FOCUSPN'],
                'ID_TRANS_DEBITUR_PERSEORANGAN' => $transDebiturPerseorangan['ID']
            ];
        }, $this->listDebiturPerseoranganModulPengurusan->toArray());

        return $this;
    }

    private function doResyncTransDebiturPerseorangan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_DEBITUR_PERSEORANGAN ke T_GLOBAL: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingDebiturPerseoranganModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tGlobal = new TGlobalRepository();
                $tGlobal->upsert($chunk->toArray(),['ID'],['ID_TRANS_DEBITUR_PERSEORANGAN']);
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
