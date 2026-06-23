<?php

namespace App\Console\Commands;

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

        $this->info('Sinkronisasi pejabat selesai');
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

    /**
     * Digunakan untuk melakukan sinkronisasi pejabat
     * @return $this
     */
    private function doSync()
    {
        // Remove all existing data
        $this->deleteOldData()
            ->saveNewData();

        return $this;
    }

    /**
     * Digunakan untuk menghapus data pejabat
     * @return $this
     */
    private function deleteOldData()
    {
        $this->refPejabatRepository->deleteByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk menyimpan data pejabat baru
     * @return $this
     */
    private function saveNewData()
    {
        $this->database::beginTransaction();
        try
        {
            foreach ($this->listPejabat as $pejabat)
            {
                $refPejabat = new RefPejabatRepository();
                $refPejabat->uuid = Str::uuid();
                $refPejabat->id_ref_satuan_kerja = $pejabat->ID_REF_SATUAN_KERJA;
                $refPejabat->id_ref_jabatan = $pejabat->ID_REF_JABATAN;
                $refPejabat->id_ref_status_pejabat = self::STATUS_PEJABAT_AKTIF;
                $refPejabat->nama = $pejabat->NAMA;
                $refPejabat->nip = $pejabat->NIP;
                $refPejabat->telepon = $pejabat->TELEPON;
                $refPejabat->email = $pejabat->EMAIL;
                $refPejabat->jenis_kelamin = $pejabat->JENIS_KELAMIN;
                $refPejabat->nomor_sk_pengangkatan = $pejabat->NOMOR_SK_PENGANGKATAN;
                $refPejabat->tanggal_sk_pengangkatan = $pejabat->TANGGAL_SK_PENGKATAN;
                $refPejabat->perihal_sk_pengangkatan = $pejabat->PERIHAL_SK_PENGKATAN;
                $refPejabat->created_by = $pejabat->CREATED_BY;
                $refPejabat->created_at = $pejabat->CREATED_AT;
                $refPejabat->updated_by = $pejabat->UPDATED_BY;
                $refPejabat->updated_at = $pejabat->UPDATED_AT;
                $refPejabat->save();
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
