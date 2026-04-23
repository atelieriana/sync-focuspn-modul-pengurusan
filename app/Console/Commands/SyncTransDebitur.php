<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransDebiturRepository;
use App\Repositories\FocusPN\TGlobalRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransDebiturRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransDebitur extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 100;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransDebiturRepository $migrasiTransDebiturRepository;
    private TransDebiturRepository $transDebiturRepository;
    private Collection $listTransDebiturFocusPN;
    private Collection $listTransDebiturModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingTransDebiturFocusPN;
    private array $remappingTransDebiturModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransDebiturRepository = new MigrasiTransDebiturRepository();
        $this->transDebiturRepository = new TransDebiturRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-debitur {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi trans piutang';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi debitur dimulai!');
        $this->setSatuanKerja()
            ->getListTransDebiturFocusPN()
            ->mappingTransDebiturFocusPN()
            ->doSync()
            ->getListTransDebiturModulPengurusan()
            ->mappingTransDebiturModulPengurusan()
            ->doResyncIdTransDebitur();
        $this->info('Sinkronisasi transaksi debitur selesai');
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

    /**
     * Digunakan untuk mendapatkan list debitur focuspn berdasarkan id satuan kerja
     * @return $this
     */
    private function getListTransDebiturFocusPN()
    {
        $this->listTransDebiturFocusPN = $this->migrasiTransDebiturRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * @return static
     */
    private function mappingTransDebiturFocusPN(): static
    {
        $this->remappingTransDebiturFocusPN = array_map(function ($transDebitur) {
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_TRANS_PIUTANG' => $transDebitur['ID_MODUL_PENGURUSAN'],
                'ID_FOCUSPN' => $transDebitur['ID_FOCUSPN'],
                'NAMA' => trim($transDebitur['NAMA']),
                'KELURAHAN' => $transDebitur['KELURAHAN'] ?? null,
                'RT_RW' => $transDebitur['RT_RW'] ?? null,
                'ALAMAT' => $transDebitur['ALAMAT'],
                'IS_BADAN_HUKUM' => $transDebitur['IS_BADAN_HUKUM'],
                'CREATED_BY' => $transDebitur['CREATED_BY'],
                'CREATED_AT' => $transDebitur['CREATED_AT'],
                'UPDATED_BY' => $transDebitur['UPDATED_BY'],
                'UPDATED_AT' => $transDebitur['UPDATED_AT'],
            ];
        }, $this->listTransDebiturFocusPN->toArray());

        return $this;
    }

    private function doSync()
    {
        $this->deleteOlData()
            ->saveNewData();

        return $this;
    }

    /**
     * Digunakan untuk mneghapus data lama
     * @return $this
     */
    private function deleteOlData(): static
    {
        $this->transDebiturRepository->deleteByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    /**
     * @return void
     */
    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_GLOBAL ke TRANS_DEBITUR: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransDebiturFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transDebitur = new TransDebiturRepository();
                $transDebitur::insert($chunk->toArray());
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
     * Digunakan untuk mendapatkan list trans debitur dari modul pengurusan
     * @return $this
     */
    private function getListTransDebiturModulPengurusan()
    {
        $this->listTransDebiturModulPengurusan = $this->transDebiturRepository->getByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk mapping
     * @return $this
     */
    private function mappingTransDebiturModulPengurusan(): static
    {
        $this->remappingTransDebiturModulPengurusan = array_map(function ($transDebitur) {
            return [
                'ID' => $transDebitur['ID_FOCUSPN'],
                'ID_TRANS_DEBITUR' => $transDebitur['ID'],
            ];
        }, $this->listTransDebiturModulPengurusan->toArray());

        return $this;
    }

    private function doResyncIdTransDebitur()
    {
        $this->info('Tahapan sinkonrisasi TRANS_DEBITUR ke T_GLOBAL: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransDebiturModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tGlobal = new TGlobalRepository();
                $tGlobal->upsert($chunk->toArray(),['ID'],['ID_TRANS_DEBITUR']);
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
