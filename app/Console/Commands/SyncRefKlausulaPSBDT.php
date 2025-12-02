<?php

namespace App\Console\Commands;

use App\Models\ModulPengurusan\RefKlausulPSBDT;
use App\Repositories\FocusPN\MigrasiRefKlausulPSBTRepository;
use App\Repositories\ModulPengurusan\RefKlausulPSBDTRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncRefKlausulaPSBDT extends Command
{
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

    private DB $database;
    private MigrasiRefKlausulPSBTRepository $migrasiRefKlausulPSBTRepository;
    private RefKlausulPSBDTRepository $refKlausulPSBDTRepository;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private string $kodeSatuanKerja;
    private int $idSatuanKerja;
    private Collection $listKlausulPSBDT;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->migrasiRefKlausulPSBTRepository = new MigrasiRefKlausulPSBTRepository();
        $this->refKlausulPSBDTRepository = new RefKlausulPSBDTRepository();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi klausul PSBDT berdasarkan kode satuan kerja dimulai');
        $this->setSatuanKerja()
            ->getListKlausulaPSBDT()
            ->doSync();
    }

    /**
     * Digunakan untuk melakukan set kode satuan kerja yang akan dilakukan sinkronisasi
     * @return $this
     */
    private function setSatuanKerja()
    {
        $this->kodeSatuanKerja = $this->argument('kode-satuan-kerja');
        $this->idSatuanKerja = $this->refSatuanKerjaRepository->getIdSatuanKerjaByKodeSatuanKerja($this->kodeSatuanKerja)->ID;
        return $this;
    }

    /**
     * Diguynakan untuk melakukan pengambilan klausula PSBDT berdasarkan kode satuan kerja
     * @return $this
     */
    public function getListKlausulaPSBDT()
    {
        $this->listKlausulPSBDT = $this->migrasiRefKlausulPSBTRepository->getByKodeSatuanKerja($this->kodeSatuanKerja);
        return $this;
    }

    public function doSync()
    {
        $this->deleteOldData()
            ->saveNewData();

        $this->info('Sinkronisasi klausul PSBDT selesai.');
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
        $this->database::beginTransaction();

        try
        {
            foreach ($this->listKlausulPSBDT as $klausulPSBDT)
            {
                $refKlausulPSBDT = new RefKlausulPSBDTRepository();
                $refKlausulPSBDT->UUID = Str::uuid();
                $refKlausulPSBDT->ID_REF_SATUAN_KERJA_KPKNL = $klausulPSBDT->ID_REF_SATUAN_KERJA_KPKNL;
                $refKlausulPSBDT->KLAUSUL_PSBDT = $klausulPSBDT->KLAUSUL_PSBDT;
                $refKlausulPSBDT->CREATED_BY = $klausulPSBDT->CREATED_BY;
                $refKlausulPSBDT->CREATED_AT = $klausulPSBDT->CREATED_AT;
                $refKlausulPSBDT->UPDATED_BY = $klausulPSBDT->UPDATED_BY;
                $refKlausulPSBDT->UPDATED_AT = $klausulPSBDT->UPDATED_AT;
                $refKlausulPSBDT->save();
            }
            $this->database::commit();
        }
        catch (Exception $e)
        {
            $this->database::rollBack();
            $this->error($e->getMessage());
        }

        return $this;
    }
}
