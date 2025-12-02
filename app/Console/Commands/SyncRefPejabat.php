<?php

namespace App\Console\Commands;

use App\Models\ModulPengurusan\RefPejabat;
use App\Repositories\FocusPN\MigrasiRefPejabatRepository;
use App\Repositories\ModulPengurusan\RefPejabatRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncRefPejabat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ref-pejabat {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi pejabat berdasarkan kode satuan kerja';

    const STATUS_PEJABAT_AKTIF = 1;
    private DB $database;
    private MigrasiRefPejabatRepository $migrasiRefPejabatRepository;
    private RefPejabatRepository $refPejabatRepository;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private string $kodeSatuanKerja;
    private int $idSatuanKerja;
    private Collection $listPejabat;


    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->migrasiRefPejabatRepository = new MigrasiRefPejabatRepository();
        $this->refPejabatRepository = new RefPejabatRepository();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi pejabat berdasarkan kode satuan kerja dimulai');
        $this->setSatuanKerja()
            ->getListPejabat()
            ->doSync();

        echo $this->idSatuanKerja;
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
     * Digunakan untuk melakukan get list pejabat yang akan dilakukan sinkronisasi
     * @return $this
     */
    private function getListPejabat()
    {
        $this->listPejabat = $this->migrasiRefPejabatRepository->getByKodeSatuanKerja($this->kodeSatuanKerja);
        return $this;
    }

    private function doSync()
    {
        // Remove all existing data
        $this->deleteOldData();

        $this->database::beginTransaction();
        try
        {
            foreach ($this->listPejabat as $pejabat)
            {
                $refPejabat = new RefPejabat();
                $refPejabat->UUID = Str::uuid();
                $refPejabat->ID_REF_SATUAN_KERJA = $pejabat->ID_REF_SATUAN_KERJA;
                $refPejabat->ID_REF_JABATAN = $pejabat->ID_REF_JABATAN;
                $refPejabat->ID_REF_STATUS_PEJABAT = self::STATUS_PEJABAT_AKTIF;
                $refPejabat->NAMA = $pejabat->NAMA;
                $refPejabat->NIP = $pejabat->NIP;
                $refPejabat->TELEPON = $pejabat->TELEPON;
                $refPejabat->EMAIL = $pejabat->EMAIL;
                $refPejabat->JENIS_KELAMIN = $pejabat->JENIS_KELAMIN;
                $refPejabat->NOMOR_SK_PENGANGKATAN = $pejabat->NOMOR_SK_PENGANGKATAN;
                $refPejabat->TANGGAL_SK_PENGANGKATAN = $pejabat->TANGGAL_SK_PENGKATAN;
                $refPejabat->PERIHAL_SK_PENGANGKATAN = $pejabat->PERIHAL_SK_PENGKATAN;
                $refPejabat->CREATED_BY = $pejabat->CREATED_BY;
                $refPejabat->CREATED_AT = $pejabat->CREATED_AT;
                $refPejabat->UPDATED_BY = $pejabat->UPDATED_BY;
                $refPejabat->UPDATED_AT = $pejabat->UPDATED_AT;
                $refPejabat->save();
            }

            $this->database::commit();
            $this->info('Sinkronisasi berhasil');
        }
        catch (Exception $e)
        {
            $this->database::rollBack();
            $this->error($e->getMessage());
        }

        return $this;
    }

    private function deleteOldData()
    {
        $this->refPejabatRepository->deleteByIdSatuanKerja($this->idSatuanKerja);
    }
}
