<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransBeritaAcaraPenyitaanRepository;
use App\Repositories\FocusPN\TBAPenyitaanRepository;
use App\Repositories\ModulPengurusan\RefJurusitaRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransBeritaAcaraPenyitaanRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransBeritaAcaraPenyitaan extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 100;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransBeritaAcaraPenyitaanRepository $migrasiTransBeritaAcaraPenyitaanRepository;
    private RefJurusitaRepository $refJurusitaRepository;
    private TransBeritaAcaraPenyitaanRepository $transBeritaAcaraPenyitaanRepository;
    private int $idSatuanKerja;
    private Collection $listTransBeritaAcaraPenyitaanFocusPN;
    private Collection $listJurusita;
    private Collection $listTransBeritaAcaraPenyitaanModulPengurusan;
    private array $remappingListTransBeritaAcaraPenyitaanFocusPN;
    private array $remappingListJurusita;
    private array $remappingListTransBeritaAcaraPenyitaanModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransBeritaAcaraPenyitaanRepository = new MigrasiTransBeritaAcaraPenyitaanRepository();
        $this->refJurusitaRepository = new RefJurusitaRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-berita-acara-penyitaan {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan sinkronisasi transaksi berita acara penyitaan berdasarkan kode satuan kerja';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mulai proses sinkronisasi transaksi berita acara penyitaan');
        $this->setSatuanKerja()
            ->getListTransBeritaAcaraPenyitaanFocusPN()
            ->getListJurusita()
            ->mappingListJurusita()
            ->mappingListTransBeritaAcaraPenyitaanFocusPN()
            ->doSync()
            ->getListTransBeritaAcaraPenyitaanModulPengurusan()
            ->resyncIdTransBeritaAcaraPenyitaanModulPengurusan();
        $this->info('Mulai proses sinkronisasi transaksi berita acara penyitaan');
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

    private function getListTransBeritaAcaraPenyitaanFocusPN()
    {
        $this->listTransBeritaAcaraPenyitaanFocusPN = $this->migrasiTransBeritaAcaraPenyitaanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk mendapatkan list jurusita berdasarkan satuan kerja KPKNL
     * @return $this
     */
    private function getListJurusita()
    {
        $this->listJurusita = $this->refJurusitaRepository->getByIdRefSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk melakukan mapping list referensi jurusita
     * @return $this
     */
    private function mappingListJurusita()
    {
        $this->remappingListJurusita = array_reduce($this->listJurusita->toArray(), function($result, $jurusitaData){
            $result[$jurusitaData['ID']] = [
                'NIP' => $jurusitaData['NIP'],
                'NAMA_LENGKAP' => $jurusitaData['NAMA_LENGKAP'],
            ];
            return $result;
        }, []);

        return $this;
    }

    private function mappingListTransBeritaAcaraPenyitaanFocusPN()
    {
        $this->remappingListTransBeritaAcaraPenyitaanFocusPN = array_map(function($transBeritaAcaraPenyitaan){
            $idRefJurusita = $this->searchReferensiJurusita($transBeritaAcaraSuratPaksa['NIP_JURUSITA'], $transBeritaAcaraSuratPaksa['NAMA_JURUSITA']);
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_TRANS_PIUTANG' => $transBeritaAcaraPenyitaan['ID_TRANS_PIUTANG'],
                'ID_TRANS_TAHAP_VERITA_ACARA_PENYITAAN' => $transBeritaAcaraPenyitaan['ID_TAHAP_PENGURUSAN'],
                'ID_TAHAP_SURAT_SITA' => $transBeritaAcaraPenyitaan['ID_TAHAP_SURAT_SITA'],
                'ID_REF_JURUSITA' => $idRefJurusita,
                'NOMOR_SK_JURUSITA' => $transBeritaAcaraPenyitaan['NOMOR_SK_JURUSITA'],
                'NAMA_SAKSI_1' => $transBeritaAcaraPenyitaan['NAMA_SAKSI_1'],
                'USIA_SAKSI_1' => $transBeritaAcaraPenyitaan['USIA_SAKSI_1'],
                'PEKERJAAN_SAKSI_1' => $transBeritaAcaraPenyitaan['PEKERJAAN_SAKSI_1'],
                'ALAMAT_SAKSI_1' => $transBeritaAcaraPenyitaan['ALAMAT_SAKSI_1'],
                'NAMA_SAKSI_2' => $transBeritaAcaraPenyitaan['NAMA_SAKSI_2'],
                'USIA_SAKSI_2' => $transBeritaAcaraPenyitaan['USIA_SAKSI_2'],
                'PEKERJAAN_SAKSI_2' => $transBeritaAcaraPenyitaan['PEKERJAAN_SAKSI_2'],
                'ALAMAT_SAKSI_2' => $transBeritaAcaraPenyitaan['ALAMAT_SAKSI_2'],
                'CREATED_BY' => $transBeritaAcaraPenyitaan['CREATED_BY'],
                'CREATED_AT' => $transBeritaAcaraPenyitaan['CREATED_AT'],
                'UPDATED_BY' => $transBeritaAcaraPenyitaan['UPDATED_BY'],
                'UPDATED_AT' => $transBeritaAcaraPenyitaan['UPDATED_AT']
            ];
        }, $this->listTransBeritaAcaraPenyitaanFocusPN->toArray());

        return $this;
    }

    /**
     * Digunakan untuk mencari referensi jurusita berdasarkan NIP dan nama lengkap
     * @return $this|int
     */
    private function searchReferensiJurusita(string $nipJurusita, string $namaJurusita)
    {
        foreach ($this->remappingRefJurusita as $key => $jurusitaData) 
        {
            if ($jurusitaData['NIP'] === $nipJurusita && $jurusitaData['NAMA_LENGKAP'] === $namaJurusita)
                return $key;
        }

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
        $this->transBeritaAcaraPenyitaanRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_BA_PENYITAAN ke TRANS_BERITA_ACARA_PENYITAAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransBeritaAcaraPenyitaanFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transBeritaAcaraPenyitaan = new TransBeritaAcaraPenyitaanRepository();
                $transBeritaAcaraPenyitaan->insert($chunk->toArray());
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

    private function getListTransBeritaAcaraPenyitaanModulPengurusan()
    {
        $this->listTransBeritaAcaraPenyitaanModulPengurusan = $this->transBeritaAcaraPenyitaanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransBeritaAcaraPenyitaanModulPengurusan()
    {
        $this->remappingListTransBeritaAcaraPenyitaanModulPengurusan = array_map(function($transBeritaAcaraPenyitaan){
            return [
                'ID' => $transBeritaAcaraPenyitaan['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transBeritaAcaraPenyitaan['ID']
            ];
        }, $this->listTransBeritaAcaraPenyitaanModulPengurusan->toArray());

        return $this;
    }

    private function resyncIdTransBeritaAcaraPenyitaanModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_BERITA_ACARA_PENYITAAN ke T_BA_PENYITAAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransBeritaAcaraPenyitaanModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tBAPenyitaan = new TBAPenyitaanRepository();
                $tBAPenyitaan->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
