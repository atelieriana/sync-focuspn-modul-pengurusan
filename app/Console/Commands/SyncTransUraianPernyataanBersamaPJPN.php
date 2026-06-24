<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransUraianPernyataanBersamaPJPNRepository;
use App\Repositories\FocusPN\TPBPJPNUraianRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransUraianPernyataanBersamaPJPNRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransUraianPernyataanBersamaPJPN extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransUraianPernyataanBersamaPJPNRepository $migrasiTransUraianPernyataanBersamaPJPNRepository;
    private TransUraianPernyataanBersamaPJPNRepository $transUraianPernyataanBersamaPJPNRepository;
    private Collection $listTransUraianPernyataanBersamaPJPNFocusPN;
    private Collection $listTransUraianPernyataanBersamaPJPNModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingTransUraianPernyataanBersamaPJPNFocusPN;
    private array $remappingTransUraianPernyataanBersamaPJPNModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransUraianPernyataanBersamaPJPNRepository = new MigrasiTransUraianPernyataanBersamaPJPNRepository();
        $this->transUraianPernyataanBersamaPJPNRepository = new TransUraianPernyataanBersamaPJPNRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-uraian-pernyataan-bersama-pjpn {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi transaksi uraian pernyataan bersama PJPN';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi tahap pengurusan!');
        $this->setSatuanKerja()
            ->getListTransUraianPernyataanBersamaPJPNFocusPN()
            ->mappingListTransUraianPernyataanBersamaPJPNFocusPN()
            ->doSync()
            ->getListTransUraianPernyataanBersamaPJPNModulPengurusan()
            ->mappingListTransUraianPernyataanBersamaPJPNModulPengurusan()
            ->resyncIdTransUraianPernyataanBersamaPJPNModulPengurusan();
        $this->info('Sinkronisasi transaksi tahap pengurusan');
    }

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

    private function getListTransUraianPernyataanBersamaPJPNFocusPN()
    {
        $this->listTransUraianPernyataanBersamaPJPNFocusPN = $this->migrasiTransUraianPernyataanBersamaPJPNRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransUraianPernyataanBersamaPJPNFocusPN()
    {
        $this->remappingTransUraianPernyataanBersamaPJPNFocusPN = array_map(function($transUraianPBPJPN){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transUraianPBPJPN['ID_FOCUSPN'],
                'id_trans_piutang' => $transUraianPBPJPN['ID_TRANS_PIUTANG'],
                'id_trans_tahap_pengurusan' => $transUraianPBPJPN['ID_TRANS_TAHAP_PENGURUSAN'],
                'uraian' => $transUraianPBPJPN['URAIAN'],
                'created_by' => $transUraianPBPJPN['CREATED_BY'],
                'created_at' => $transUraianPBPJPN['CREATED_AT'],
                'updated_by' => $transUraianPBPJPN['UPDATED_BY'],
                'updated_at' => $transUraianPBPJPN['UPDATED_AT']
            ];
        }, $this->listTransUraianPernyataanBersamaPJPNFocusPN->toArray());
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
        $this->transUraianPernyataanBersamaPJPNRepository->deleteByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_URAIAN_PB_PJPN ke TRANS_URAIAN_PERNYATAAN_BERSAMA_PJPN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransUraianPernyataanBersamaPJPNFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transUraianPernyataanBersamaPJPNRepository = new TransUraianPernyataanBersamaPJPNRepository();
                $transUraianPernyataanBersamaPJPNRepository->insert($chunk->toArray());
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

    private function getListTransUraianPernyataanBersamaPJPNModulPengurusan()
    {
        $this->listTransUraianPernyataanBersamaPJPNModulPengurusan = $this->transUraianPernyataanBersamaPJPNRepository->getByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransUraianPernyataanBersamaPJPNModulPengurusan()
    {
        $this->remappingTransUraianPernyataanBersamaPJPNModulPengurusan = array_map(function($transUraianPBPJPN){
            return [
                'ID' => $transUraianPBPJPN['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transUraianPBPJPN['id']
            ];
        }, $this->listTransUraianPernyataanBersamaPJPNModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransUraianPernyataanBersamaPJPNModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_URAIAN_PERNYATAAN_BERSAMA_PJPN ke T_URAIAN_PB_PJPN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransUraianPernyataanBersamaPJPNModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tPBPJPNUraianRepository = new TPBPJPNUraianRepository();
                $tPBPJPNUraianRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
