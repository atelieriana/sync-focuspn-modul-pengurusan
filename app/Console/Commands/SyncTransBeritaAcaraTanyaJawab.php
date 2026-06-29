<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransBeritaAcaraTanyaJawabRepository;
use App\Repositories\FocusPN\TBATJRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransBeritaAcaraTanyaJawabRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransBeritaAcaraTanyaJawab extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 1;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransBeritaAcaraTanyaJawabRepository $migrasiTransBeritaAcaraTanyaJawabRepository;
    private TransBeritaAcaraTanyaJawabRepository $transBeritaAcaraTanyaJawabRepository;
    private int $idSatuanKerja;
    private Collection $listTransBeritaAcaraTanyaJawabFocusPN;
    private Collection $listTransBeritaAcaraTanyaJawabModulPengurusan;
    private array $remappingListTransBeritaAcaraTanyaJawabFocusPN;
    private array $remappingListTransBeritaAcaraTanyaJawabModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransBeritaAcaraTanyaJawabRepository = new MigrasiTransBeritaAcaraTanyaJawabRepository();
        $this->transBeritaAcaraTanyaJawabRepository = new TransBeritaAcaraTanyaJawabRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-berita-acara-tanya-jawab {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk mensinkronkan data transaksi berita acara tanya jawab berdasarkan kode satuan kerja KPKNL dari Focus PN ke Modul Pengurusan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mulai proses sinkronisasi transaksi berita acara surat paksa');
        $this->setSatuanKerja()
            ->getListTransBeritaAcaraTanyaJawabFocusPN()
            ->mappingListTransBeritaAcaraTanyaJawabFocusPN()
            ->doSync()
            ->getListTransBeritaAcaraTanyaJawabModulPengurusan()
            ->mappingListTransBeritaAcaraTanyaJawabModulPengurusan()
            ->doResyncTransBeritaAcaraTanyaJawabModulPengurusan();
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
            $this->idSatuanKerja = $satuanKerja->id;
        else
            $this->error('Kode satuan kerja tidak ditemukan');
        return $this;
    }

    private function getListTransBeritaAcaraTanyaJawabFocusPN()
    {
        $this->listTransBeritaAcaraTanyaJawabFocusPN = $this->migrasiTransBeritaAcaraTanyaJawabRepository->getByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransBeritaAcaraTanyaJawabFocusPN()
    {
        $this->remappingListTransBeritaAcaraTanyaJawabFocusPN = array_map(function($transBeritaAcaraTanyaJawab){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transBeritaAcaraTanyaJawab['ID_FOCUSPN'],
                'id_trans_piutang' => $transBeritaAcaraTanyaJawab['ID_TRANS_PIUTANG'],
                'id_tahap_pengurusan' => $transBeritaAcaraTanyaJawab['ID_TAHAP_PENGURUSAN'],
                'nama_pewawancara' => $transBeritaAcaraTanyaJawab['NAMA_PEWAWANCARA'],
                'nip_pewawancara' => $transBeritaAcaraTanyaJawab['NIP_PEWAWANCARA'],
                'jabatan_pewawancara' => $transBeritaAcaraTanyaJawab['JABATAN_PEWAWANCARA'],
                'nama_saksi_kpknl' => $transBeritaAcaraTanyaJawab['NAMA_SAKSI_KPKNL'],
                'nip_saksi_kpknl' => $transBeritaAcaraTanyaJawab['NIP_SAKSI_KPKNL'],
                'nama_saksi_penanggung_hutang' => $transBeritaAcaraTanyaJawab['NAMA_SAKSI_PH'],
                'waktu_tanya_jawab' => $transBeritaAcaraTanyaJawab['WAKTU_TANYA_JAWAB'],
                'created_by' => $transBeritaAcaraTanyaJawab['CREATED_BY'],
                'created_at' => $transBeritaAcaraTanyaJawab['CREATED_AT'],
                'updated_by' => $transBeritaAcaraTanyaJawab['UPDATED_BY'],
                'updated_at' => $transBeritaAcaraTanyaJawab['UPDATED_AT']
            ];
        }, $this->listTransBeritaAcaraTanyaJawabFocusPN->toArray());

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
        $this->transBeritaAcaraTanyaJawabRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_BATJ ke TRANS_BERITA_ACARA_TANYA_JAWAB: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransBeritaAcaraTanyaJawabFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transBeritaAcaraTanyaJawab = new TransBeritaAcaraTanyaJawabRepository();
                $transBeritaAcaraTanyaJawab->insert($chunk->toArray());
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

    private function getListTransBeritaAcaraTanyaJawabModulPengurusan()
    {
        $this->listTransBeritaAcaraTanyaJawabModulPengurusan = $this->transBeritaAcaraTanyaJawabRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransBeritaAcaraTanyaJawabModulPengurusan()
    {
        $this->remappingListTransBeritaAcaraTanyaJawabModulPengurusan = array_map(function ($transBeritaAcaraTanyaJawab){
            return [
                'ID' => $transBeritaAcaraTanyaJawab['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transBeritaAcaraTanyaJawab['id'],
            ];
        }, $this->listTransBeritaAcaraTanyaJawabModulPengurusan->toArray());

        return $this;
    }

    private function doResyncTransBeritaAcaraTanyaJawabModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_BERITA_ACARA_TANYA_JAWAB ke T_BATJ: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransBeritaAcaraTanyaJawabModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tBATJ = new TBATJRepository();
                $tBATJ->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
