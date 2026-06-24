<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiRefKlausulPSBTRepository;
use App\Repositories\FocusPN\RKlausulaPSBDTRepository;
use App\Repositories\ModulPengurusan\RefKlausulPSBDTRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncRefKlausulaPSBDT extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private RefKlausulPSBDTRepository $refKlausulPSBDTRepository;
    private MigrasiRefKlausulPSBTRepository $migrasiRefKlausulPSBDTRepository;
    private Collection $listKlausulPSBDTFocusPN;
    private Collection $listKlausulPSBDTModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListKlausulPSBDTFocusPN;
    private array $remappingListKlausulPSBDTModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->refKlausulPSBDTRepository = new RefKlausulPSBDTRepository();
        $this->migrasiRefKlausulPSBDTRepository = new MigrasiRefKlausulPSBTRepository();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ref-klausul-psbdt {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan melakukan sinkronisasi ref klausul psbdt tanpa kode KPKNL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi klausul PSBDT berdasarkan kode satuan kerja dimulai');
        $this->setSatuanKerja()
            ->getListKlausulaPSBDTFocusPN()
            ->mappingListKlausulaPSBDTFocusPN()
            ->doSync()
            ->getListKlausulaPSBDTModulPengurusan()
            ->mappingListKlausulaPSBDTModulPengurusan()
            ->resyncIdRefKlausulaPSBDTModulPengurusan();

        $this->info('Sinkronisasi klausul PSBDT selesai.');
    }

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

    public function getListKlausulaPSBDTFocusPN()
    {
        $this->listKlausulPSBDTFocusPN = $this->migrasiRefKlausulPSBDTRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListKlausulaPSBDTFocusPN()
    {
        $this->remappingListKlausulPSBDTFocusPN = array_map(function($refKlausulPSBDT){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $refKlausulPSBDT['ID_FOCUSPN'],
                'id_ref_satuan_kerja_kpknl' => $refKlausulPSBDT['ID_REF_SATUAN_KERJA_KPKNL'],
                'klausul_psbdt' => $refKlausulPSBDT['KLAUSUL_PSBDT'],
                'created_by' => $refKlausulPSBDT['CREATED_BY'],
                'created_at' => $refKlausulPSBDT['CREATED_AT'],
                'updated_by' => $refKlausulPSBDT['UPDATED_BY'],
                'updated_at' => $refKlausulPSBDT['UPDATED_AT']
            ];
        }, $this->listKlausulPSBDTFocusPN->toArray());
        return $this;
    }

    public function doSync()
    {
        $this->deleteOldData()
            ->saveNewData();
        return $this;
    }

    /**
     * Digunakan untuk melakukan penghapusan data klausul PSBDT
     * @return $this
     */
    public function deleteOldData()
    {
        $this->refKlausulPSBDTRepository->deleteByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk menyimpan data klausul psbdt baru
     * @return $this
     */
    public function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi R_KLAUSULA_PSBDT ke REF_KLAUSUL_PSBDT: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListKlausulPSBDTFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $refKlausulPSBDTRepository = new RefKlausulPSBDTRepository();
                $refKlausulPSBDTRepository->insert($chunk->toArray());
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

    private function getListKlausulaPSBDTModulPengurusan()
    {
        $this->listKlausulPSBDTModulPengurusan = $this->refKlausulPSBDTRepository->getByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function mappingListKlausulaPSBDTModulPengurusan()
    {
        $this->remappingListKlausulPSBDTModulPengurusan = array_map(function($refKlausulPSBDT){
            return [
                'ID' => $refKlausulPSBDT['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $refKlausulPSBDT['id']
            ];
        }, $this->listKlausulPSBDTModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdRefKlausulaPSBDTModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi REF_KLAUSUL_PSBDT ke R_KLAUSULA_PSBDT: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListKlausulPSBDTModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $rKlausulaPSBDTRepository = new RKlausulaPSBDTRepository();
                $rKlausulaPSBDTRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
