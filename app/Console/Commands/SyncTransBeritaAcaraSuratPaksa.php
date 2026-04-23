<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransBeritaAcaraSuratPaksaRepository;
use App\Repositories\FocusPN\TBASPRepository;
use App\Repositories\ModulPengurusan\RefJurusitaRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransBeritaAcaraSuratPaksaRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransBeritaAcaraSuratPaksa extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransBeritaAcaraSuratPaksaRepository $migrasiTransBeritaAcaraSuratPaksaRepository;
    private RefJurusitaRepository $refJurusitaRepository;
    private TransBeritaAcaraSuratPaksaRepository $transBeritaAcaraSuratPaksaRepository;
    private int $idSatuanKerja = 0;
    private Collection $listTransBeritaAcaraSuratPaksaFocusPN;
    private Collection $listJurusita;
    private Collection $listTransBeritaAcaraSuratPaksaModulPengurusan;
    private array $remappingTransBeritaAcaraSuratPaksaFocusPN;
    private array $remappingRefJurusita;
    private array $remappingListTransBeritaAcaraSuratPaksaModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransBeritaAcaraSuratPaksaRepository = new MigrasiTransBeritaAcaraSuratPaksaRepository();
        $this->refJurusitaRepository = new RefJurusitaRepository();
        $this->transBeritaAcaraSuratPaksaRepository = new TransBeritaAcaraSuratPaksaRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-berita-acara-surat-paksa {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi transaksi berita acara surat paksa dari Focus PN ke Modul Pengurusan berdasarkan kode satuan kerja KPKNL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mulai proses sinkronisasi transaksi berita acara surat paksa');
        $this->setSatuanKerja()
            ->getListTransBeritaAcaraSuratPaksaFocusPN()
            ->getListRefJurusita()
            ->mappingListRefJurusita()
            ->mappingListTransBeritaAcaraSuratPaksaFocusPN()
            ->doSync()
            ->getListTransBeritaAcaraSuratPaksaModulPengurusan()
            ->mappingListTransBeritaAcaraSuratPaksaModulPengurusan()
            ->doResyncIdTransBeritaAcaraSuratPaksaModulPengurusan();
        $this->info('Proses sinkronisasi transaksi berita acara surat paksa selesai');
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
     * Digunakan untuk mendapatkan list transaksi berita acara surat paksa dari Focus PN
     * @return $this
     */
    private function getListTransBeritaAcaraSuratPaksaFocusPN()
    {
        $this->listTransBeritaAcaraSuratPaksaFocusPN = $this->migrasiTransBeritaAcaraSuratPaksaRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk mendapatkan list jurusita berdasarkan satuan kerja KPKNL
     * @return $this
     */
    private function getListRefJurusita()
    {
        $this->listJurusita = $this->refJurusitaRepository->getByIdRefSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk melakukan mapping list referensi jurusita
     * @return $this
     */
    private function mappingListRefJurusita()
    {
        $this->remappingRefJurusita = array_reduce($this->listJurusita->toArray(), function($result, $jurusitaData){
            $result[$jurusitaData['ID']] = [
                'NIP' => $jurusitaData['NIP'],
                'NAMA_LENGKAP' => $jurusitaData['NAMA_LENGKAP'],
                'NOMOR_SK_JURUSITA' => $jurusitaData['NOMOR_SK_PENGANGKATAN']
            ];
            return $result;
        }, []);

        return $this;
    }
    
    /**
     * Digunakan untuk melakukan mapping list transaksi berita acara surat paksa dari Focus PN
     * @return $this
     */
    private function mappingListTransBeritaAcaraSuratPaksaFocusPN()
    {
        $this->remappingTransBeritaAcaraSuratPaksaFocusPN = array_map(function($transBeritaAcaraSuratPaksa){
            $idRefJurusita = $this->searchReferensiJurusita($transBeritaAcaraSuratPaksa['NIP_JURUSITA'], 
                                                            $transBeritaAcaraSuratPaksa['NAMA_JURUSITA'],
                                                            $transBeritaAcaraSuratPaksa['NOMOR_SK_JURUSITA']);
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_TRANS_PIUTANG' => $transBeritaAcaraSuratPaksa['ID_TRANS_PIUTANG'],
                'ID_TAHAP_PENGURUSAN' => $transBeritaAcaraSuratPaksa['ID_TAHAP_PENGURUSAN'],
                'ID_REF_JURUSITA' => $idRefJurusita,
                'ID_FOCUSPN' => $transBeritaAcaraSuratPaksa['ID_FOCUSPN'],
                'NOMOR_SK_JURUSITA' => $transBeritaAcaraSuratPaksa['NOMOR_SK_JURUSITA'],
                'NOMOR_SURAT_TUGAS' => $transBeritaAcaraSuratPaksa['NOMOR_SURAT_TUGAS'],
                'TANGGAL_SURAT_TUGAS' => $transBeritaAcaraSuratPaksa['TANGGAL_SURAT_TUGAS'],
                'NAMA_SAKSI_1' => $transBeritaAcaraSuratPaksa['NAMA_SAKSI_1'],
                'USIA_SAKSI_1' => $transBeritaAcaraSuratPaksa['USIA_SAKSI_1'],
                'ALAMAT_SAKSI_1' => $transBeritaAcaraSuratPaksa['ALAMAT_SAKSI_1'],
                'NAMA_SAKSI_2' => $transBeritaAcaraSuratPaksa['NAMA_SAKSI_2'],
                'USIA_SAKSI_2' => $transBeritaAcaraSuratPaksa['USIA_SAKSI_2'],
                'ALAMAT_SAKSI_2' => $transBeritaAcaraSuratPaksa['ALAMAT_SAKSI_2'],
                'WAKTU_PENYAMPAIAN_SURAT_PAKSA' => $transBeritaAcaraSuratPaksa['WAKTU_SURAT_PAKSA'],
                'LOKASI_PENYITAAN' => $transBeritaAcaraSuratPaksa['LOKASI_PENYITAAN'],
                'CREATED_BY' => $transBeritaAcaraSuratPaksa['CREATED_BY'],
                'CREATED_AT' => $transBeritaAcaraSuratPaksa['CREATED_AT'],
                'UPDATED_BY' => $transBeritaAcaraSuratPaksa['UPDATED_BY'],
                'UPDATED_AT' => $transBeritaAcaraSuratPaksa['UPDATED_AT'],
            ];
        }, $this->listTransBeritaAcaraSuratPaksaFocusPN->toArray());
        return $this;
    }

    /**
     * Digunakan untuk mencari referensi jurusita berdasarkan NIP dan nama lengkap
     * @return $this|int
     */
    private function searchReferensiJurusita(string $nipJurusita, string $namaJurusita, string $nomorSKJurusita)
    {
        foreach ($this->remappingRefJurusita as $key => $jurusitaData) 
        {
            if ($jurusitaData['NIP'] == $nipJurusita 
                && $jurusitaData['NAMA_LENGKAP'] == $namaJurusita
                && trim($jurusitaData['NOMOR_SK_JURUSITA']) == trim($nomorSKJurusita)) {
                return $key;
            }
        }

        return $this;
    }

    /**
     * Digunakan untuk melakukan sinkronisasi data
     * @return $this
     */
    private function doSync()
    {
        $this->deleteOldData()
            ->saveNewData();

        return $this;
    }

    /**
     * Digunakan untuk menghapus data lama sebelum sinkronisasi
     * @return $this
     */
    private function deleteOldData()
    {
        $this->transBeritaAcaraSuratPaksaRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk menyimpan data baru hasil sinkronisasi
     */
    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_BASP ke TRANS_BERITA_ACARA_SURAT_PAKSA: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransBeritaAcaraSuratPaksaFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transBeritaAcaraSuratPaksa = new TransBeritaAcaraSuratPaksaRepository();
                $transBeritaAcaraSuratPaksa->insert($chunk->toArray());
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

        return $this;
    }

    private function getListTransBeritaAcaraSuratPaksaModulPengurusan()
    {
        $this->listTransBeritaAcaraSuratPaksaModulPengurusan = $this->transBeritaAcaraSuratPaksaRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransBeritaAcaraSuratPaksaModulPengurusan()
    {
        $this->remappingListTransBeritaAcaraSuratPaksaModulPengurusan = array_map(function ($transBeritaAcaraSuratPaksa){
            return [
                'ID' => $transBeritaAcaraSuratPaksa['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transBeritaAcaraSuratPaksa['ID']
            ];
        }, $this->listTransBeritaAcaraSuratPaksaModulPengurusan->toArray());

        return $this;
    }

    private function doResyncIdTransBeritaAcaraSuratPaksaModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_BERITA_ACARA_SURAT_PAKSA ke T_BASP: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransBeritaAcaraSuratPaksaModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tBASP = new TBASPRepository();
                $tBASP->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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

        return $this;
    }
}