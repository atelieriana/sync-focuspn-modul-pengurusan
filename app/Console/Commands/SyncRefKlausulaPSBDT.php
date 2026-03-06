<?php

namespace App\Console\Commands;

use App\Models\ModulPengurusan\RefKlausulPSBDT;
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
    private MigrasiRefKlausulPSBTRepository $migrasiRefKlausulPSBTRepository;
    private RefKlausulPSBDTRepository $refKlausulPSBDTRepository;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private Collection $listKlausulPSBDTFocusPN;
    private Collection $listKlausulPSBDTModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListKlausulPSBDTFocusPN;
    private array $remappingListKlausulPSBDTModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->migrasiRefKlausulPSBTRepository = new MigrasiRefKlausulPSBTRepository();
        $this->refKlausulPSBDTRepository = new RefKlausulPSBDTRepository();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
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
    protected $description = 'Digunakan untuk melakukan sinkronisasi klausula PSBDT berdasarkan kode satuan kerja';

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

    /**
     * Digunakan untuk melakukan set kode satuan kerja yang akan dilakukan sinkronisasi
     * @return $this
     */
    private function setSatuanKerja()
    {
        $kodeSatuanKerja = $this->argument('kode-satuan-kerja');
        $this->idSatuanKerja = $this->refSatuanKerjaRepository->getIdSatuanKerjaByKodeSatuanKerja($kodeSatuanKerja)->ID;
        return $this;
    }

    /**
     * Diguynakan untuk melakukan pengambilan klausula PSBDT berdasarkan kode satuan kerja
     * @return $this
     */
    public function getListKlausulaPSBDTFocusPN()
    {
        $this->listKlausulPSBDTFocusPN = $this->migrasiRefKlausulPSBTRepository->getByKodeSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function mappingListKlausulaPSBDTFocusPN()
    {
        $this->remappingListKlausulPSBDTFocusPN = array_map(function($refKlausulPSBDT){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $refKlausulPSBDT['ID_FOCUSPN'],
                'ID_REF_SATUAN_KERJA_KPKNL' => $refKlausulPSBDT['ID_REF_SATUAN_KERJA_KPKNL'],
                'KLAUSUL_PSBDT' => $refKlausulPSBDT['KLAUSUL_PSBDT'],
                'CREATED_BY' => $refKlausulPSBDT['CREATED_BY'],
                'CREATED_AT' => $refKlausulPSBDT['CREATED_AT'],
                'UPDATED_BY' => $refKlausulPSBDT['UPDATED_BY'],
                'UPDATED_AT' => $refKlausulPSBDT['UPDATED_AT']
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
                'ID' => $refKlausulPSBDT['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $refKlausulPSBDT['ID']
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
