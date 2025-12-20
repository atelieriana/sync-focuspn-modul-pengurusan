<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransNIlaiSP3NRepository;
use App\Repositories\FocusPN\TNilaiSP3NRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransNilaiSP3NRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransNilaiSP3N extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 100;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransNIlaiSP3NRepository $migrasiTransNIlaiSP3NRepository;
    private TransNilaiSP3NRepository $transNilaiSP3NRepository;
    private Collection $listTransNilaiSP3NFocusPN;
    private Collection $listTransNilaiSP3NModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingListTransNilaiSP3NFocusPN;
    private array $remappingListTransNilaiSP3NModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransNIlaiSP3NRepository = new MigrasiTransNIlaiSP3NRepository();
        $this->transNilaiSP3NRepository = new TransNilaiSP3NRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-nilai-sp3n {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi nilai SP3N';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi nilai SP3N dimulai!');
        $this->setSatuanKerja()
            ->getListTransNilaiSP3NFocusPN()
            ->mappingListTransNilaiSP3NFocusPN()
            ->doSync()
            ->getListTransNilaiSP3NModulPengurusan()
            ->mappingListTransNilaiSP3NModulPengurusan()
            ->doResycnTransNilaiSP3NModulPengurusan();
        $this->info('Sinkronisasi transaksi nilai SP3N selesai');
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

    private function getListTransNilaiSP3NFocusPN()
    {
        $this->listTransNilaiSP3NFocusPN = $this->migrasiTransNIlaiSP3NRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransNilaiSP3NFocusPN()
    {
        $this->remappingListTransNilaiSP3NFocusPN = array_map(function ($transNilaiSP3N) {
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_TRANS_PIUTANG' => $transNilaiSP3N['ID_TRANS_PIUTANG_MODUL_PENGURUSAN'],
                'ID_TRANS_TAHAP_PENGURUSAN' => $transNilaiSP3N['ID_TRANS_TAHAP_PENGURUSAN'],
                'ID_REF_MATA_UANG' => $transNilaiSP3N['ID_REF_MATA_UANG'],
                'POKOK' => $transNilaiSP3N['POKOK'],
                'BUNGA' => $transNilaiSP3N['BUNGA'],
                'DENDA' => $transNilaiSP3N['DENDA'],
                'LAINNYA' => $transNilaiSP3N['LAINNYA'],
                'CREATED_BY' => $transNilaiSP3N['CREATED_BY'],
                'CREATED_AT' => $transNilaiSP3N['CREATED_AT'],
                'UPDATED_BY' => $transNilaiSP3N['UPDATED_BY'],
                'UPDATED_AT' => $transNilaiSP3N['UPDATED_AT'],
            ];
        }, $this->listTransNilaiSP3NFocusPN->toArray());

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
        $this->transNilaiSP3NRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_NILAISP3N ke TRANS_NILAI_SP3N: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransNilaiSP3NFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transNilaiSP3N = new TransNilaiSP3NRepository();
                $transNilaiSP3N->insert($chunk->toArray());
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

    private function getListTransNilaiSP3NModulPengurusan()
    {
        $this->listTransNilaiSP3NModulPengurusan = $this->transNilaiSP3NRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransNilaiSP3NModulPengurusan()
    {
        $this->remappingListTransNilaiSP3NModulPengurusan = array_map(function ($transNilaiSP3N) {
            return [
                'ID' => $transNilaiSP3N['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transNilaiSP3N['ID'],
            ];
        }, $this->listTransNilaiSP3NModulPengurusan->toArray());

        return $this;
    }

    private function doResycnTransNilaiSP3NModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi T_NILAISP3N ke TRANS_NILAI_SP3N: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransNilaiSP3NModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));

            foreach ($chunkData as $chunk)
            {
                $transNilaiSP3N = new TNilaiSP3NRepository();
                $transNilaiSP3N->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
