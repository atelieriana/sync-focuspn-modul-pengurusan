<?php

namespace App\Console\Commands;

use App\Models\ModulPengurusan\TransPiutang;
use App\Repositories\FocusPN\MigrasiTransPiutangRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransPiutangRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransPiutang extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-piutang {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi trans piutang';

    const TOTAL_DATA_EACH_CHUNK = 100;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransPiutangRepository $migrasiTransPiutangRepository;
    private TransPiutangRepository $transPiutangRepository;
    private string $kodeSatuanKerja;
    private int $idSatuanKerja = 0;
    private Collection $listTransPiutang;
    private array $reMappingListTransPiutang;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransPiutangRepository = new MigrasiTransPiutangRepository();
        $this->transPiutangRepository = new TransPiutangRepository();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi piutang dimulai!');
        $this->setSatuanKerja()
            ->getListTransPiutang()
            ->mappingTransPiutang()
            ->doSync();
        $this->info('Sinkronisasi transaksi piutang selesai');
    }

    /**
     * Digunakan untuk melakukan set kode satuan kerja yang akan dilakukan sinkronisasi
     * @return $this
     */
    private function setSatuanKerja()
    {
        $this->kodeSatuanKerja = $this->argument('kode-satuan-kerja');
        $satuanKerja = $this->refSatuanKerjaRepository->getIdSatuanKerjaByKodeSatuanKerja($this->kodeSatuanKerja);
        if (!is_null($satuanKerja))
            $this->idSatuanKerja = $satuanKerja->ID;
        else
            $this->error('Kode satuan kerja tidak ditemukan');
        return $this;
    }

    /**
     * @return $this
     */
    private function getListTransPiutang()
    {
        $this->listTransPiutang = $this->migrasiTransPiutangRepository->getByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    /**
     * Added UUID
     * @return $this
     */
    private function mappingTransPiutang()
    {
        $this->reMappingListTransPiutang = array_map(function ($transPiutang) {
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_REF_SATUAN_KERJA_KPKNL' => $transPiutang['ID_REF_SATUAN_KERJA_KPKNL'],
                'ID_REF_SATUAN_KERJA_KREDITUR' => $transPiutang['ID_REF_SATUAN_KERJA_KREDITUR'],
                'ID_REF_KLASIFIKASI_PIUTANG' => $transPiutang['ID_REF_KLASIFIKASI_PIUTANG'],
                'ID_REF_SUMBER_PENYERAHAN' => $transPiutang['ID_REF_SUMBER_PENYERAHAN'],
                'ID_REF_JENIS_USAHA' => null,
                'TANGGAL_TERJADI_PIUTANG' => $transPiutang['TANGGAL_TERJADI_PIUTANG'],
                'TANGGAL_JATUH_TEMPO' => $transPiutang['TANGGAL_JATUH_TEMPO'],
                'NOMOR_AGENDA' => $transPiutang['NOMOR_AGENDA'],
                'TANGGAL_AGENDA' => $transPiutang['TANGGAL_AGENDA'],
                'NOMOR_PENYERAHAN' => $transPiutang['NOMOR_PENYERAHAN'],
                'TANGGAL_PENYERAHAN' => $transPiutang['TANGGAL_PENYERAHAN'],
                'KODE_PIUTANG' => null,
                'NOMOR_PIUTANG' => null,
                'NOMOR_REGISTER_PIUTANG' => $transPiutang['NOMOR_REGISTER_PIUTANG'],
                'BULAN_REGISTER_PIUTANG' => $transPiutang['BULAN_REGISTER_PIUTANG'],
                'TAHUN_REGISTER_PIUTANG' => $transPiutang['TAHUN_REGISTER_PIUTANG'],
                'KEADAAN_USAHA' => $transPiutang['KEADAAN_USAHA'],
                'PERMASALAHAN_PIUTANG' => $transPiutang['PERMASALAHAN_PIUTANG'] ?? '-',
                'PENDAPAT' => $transPiutang['PENDAPAT'] ?? '-',
                'SARAN' => $transPiutang['SARAN'] ?? '-',
                'CREATED_BY' => $transPiutang['CREATED_BY'] ?? 'Migrasi FocusPN',
                'CREATED_AT' => $transPiutang['CREATED_AT'],
                'UPDATED_BY' => $transPiutang['UPDATED_BY'] ?? 'Migrasi FocusPN',
                'UPDATED_AT' => $transPiutang['UPDATED_AT'],
                'ID_FOCUSPN' => $transPiutang['ID_FOCUSPN'],
            ];
        }, $this->listTransPiutang->toArray());
        return $this;
    }

    private function doSync()
    {
        $this->deleteOldData()
            ->saveNewData();
    }

    /**
     * Digunakan untuk melakukan penghapusan data klausul PSBDT
     * @return $this
     */
    public function deleteOldData()
    {
        $this->transPiutangRepository->deleteByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk menyimpan data
     * @return void
     */
    private function saveNewData()
    {
        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->reMappingListTransPiutang)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            foreach ($chunkData as $chunk)
            {
                $transPiutang = new TransPiutangRepository();
                $transPiutang::insert($chunk->toArray());
            }

            $this->database::commit();
        }
        catch (Exception $exception)
        {
            $this->database::rollBack();
            $this->error($exception->getMessage());
        }
    }
}
