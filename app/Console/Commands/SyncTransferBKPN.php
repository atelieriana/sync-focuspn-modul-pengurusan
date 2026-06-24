<?php

namespace App\Console\Commands;

use Exception;
use App\Repositories\FocusPN\MigrasiTransferBKPNRepository;
use App\Repositories\FocusPN\TTransferArsipRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransferBKPNRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransferBKPN extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 500;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransferBKPNRepository $migrasiTransferBKPNRepository;
    private TransferBKPNRepository $transferBKPNRepository;
    private Collection $listTransferBKPNFocusPN;
    private Collection $listTransferBKPBModulPengurusan;
    private int $idSatuanKerjaKPKNL;
    private array $remappingListTransferBKPNFocusPN;
    private array $remappingListTransferBKPNModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransferBKPNRepository = new MigrasiTransferBKPNRepository();
        $this->transferBKPNRepository = new TransferBKPNRepository();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:transfer-bkpn {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan transfer BKPN berdasarkan kode satuan kerja KPKNL asal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transfer BKPN antar KPKNL dimulai!');
        $this->setSatuanKerja()
            ->getListTransferBKPNFocusPN()
            ->mappingListTransferBKPNFocusPN()
            ->doSync()
            ->getListTransferBKPNModulPengurusan()
            ->mappingListTransferBKPNModulPengurusan()
            ->resyncIdTransferBKPNModulPengurusan();
        $this->info('Sinkronisasi transfer BKPN antar KPKNL selesai');
    }

    private function setSatuanKerja()
    {
        $kodeSatuanKerja = $this->argument('kode-satuan-kerja');
        $satuanKerja = $this->refSatuanKerjaRepository->getIdSatuanKerjaByKodeSatuanKerja($kodeSatuanKerja);
        if (!is_null($satuanKerja))
            $this->idSatuanKerjaKPKNL = $satuanKerja->ID;
        else
            $this->error('Kode satuan kerja tidak ditemukan');
        return $this;
    }

    private function getListTransferBKPNFocusPN()
    {
        $this->listTransferBKPNFocusPN = $this->migrasiTransferBKPNRepository->getByIdSatuanKerjaKPKNLAsal($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransferBKPNFocusPN()
    {
        $this->remappingListTransferBKPNFocusPN = array_map(function($transferBKPN){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_satuan_kerja_kpknl_asal' => $transferBKPN['ID_SATUAN_KERJA_KPKNL_ASAL'],
                'id_satuan_kerja_kpknl_tujuan' => $transferBKPN['ID_SATUAN_KERJA_KPKNL_TUJUAN'],
                'id_ref_status_transfer_bkpn' => $transferBKPN['ID_REF_STATUS_TRANSFER_BKPN'],
                'nomor_ba_penyerahan' => $transferBKPN['NOMOR_BA'],
                'tanggal_ba_penyerahan' => $transferBKPN['TANGGAL_BA'],
                'created_by' => $transferBKPN['CREATED_BY'],
                'created_at' => $transferBKPN['CREATED_AT'],
                'updated_by' => $transferBKPN['UPDATED_BY'],
                'updated_at' => $transferBKPN['UPDATED_AT']
            ];
        }, $this->listTransferBKPNFocusPN->toArray());

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
        $this->transferBKPNRepository->deleteByIdSatuanKerjaKPKNLAsal($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_TRANSFER_ARSIP ke TRANSFER_BKPN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransferBKPNFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transferBKPN = new TransferBKPNRepository();
                $transferBKPN->insert($chunk->toArray());
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

    private function getListTransferBKPNModulPengurusan()
    {
        $this->listTransferBKPBModulPengurusan = $this->transferBKPNRepository->getByIdSatuanKerjaKPKNLAsal($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransferBKPNModulPengurusan()
    {
        $this->remappingListTransferBKPNModulPengurusan = array_map(function($transferBKPN){
            return [
                'ID_TRANSFER_BKPN' => $transferBKPN['id'],
                'NOMOR_BA' => $transferBKPN['nomor_ba_penyerahan'],
            ];
        }, $this->listTransferBKPBModulPengurusan->toArray());

        return $this;
    }

    private function resyncIdTransferBKPNModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANSFER_BKPN ke T_TRANSFER_ARSIP: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransferBKPNModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tTransferArsip = new TTransferArsipRepository();
                $tTransferArsip->upsert($chunk->toArray(),['NOMOR_BA'],['ID_TRANSFER_BKPN']);
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
