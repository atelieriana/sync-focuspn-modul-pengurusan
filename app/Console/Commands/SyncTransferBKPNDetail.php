<?php

namespace App\Console\Commands;

use Exception;
use App\Repositories\FocusPN\MigrasiTransferBKPNDetailRepository;
use App\Repositories\FocusPN\TTransferArsipRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransferBKPNDetailRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransferBKPNDetail extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 500;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransferBKPNDetailRepository $migrasiTransferBKPNDetailRepository;
    private TransferBKPNDetailRepository $transferBKPNDetailRepository;
    private Collection $listTransferBKPNDetailFocusPN;
    private Collection $listTransferBKPNDetailModulPengurusan;
    private int $idSatuanKerjaKPKNL;
    private array $remappingListTransferBKPNDetailFocusPN;
    private array $remappingListTransferBKPNDetailModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransferBKPNDetailRepository = new MigrasiTransferBKPNDetailRepository();
        $this->transferBKPNDetailRepository = new TransferBKPNDetailRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:transfer-bkpn-detail {kode-satuan-kerja : kode satuan kerja 6 digit} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk sinkronisasi data transfer BKPN detail';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transfer BKPN antar KPKNL dimulai!');
        $this->setSatuanKerja()
            ->getListTransferBKPNDetailFocusPN()
            ->mappingListTransferBKPNDetailFocusPN()
            ->doSync()
            ->getListTransferBKPNDetailModulPengurusan()
            ->mappingListTransferBKPNDetailModulPengurusan()
            ->resyncIdTransferBKPNDetailModulPengurusan();
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

    private function getListTransferBKPNDetailFocusPN()
    {
        $this->listTransferBKPNDetailFocusPN = $this->migrasiTransferBKPNDetailRepository->getByIdSatuanKerjaKPKNLAsal($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransferBKPNDetailFocusPN()
    {
        $this->remappingListTransferBKPNDetailFocusPN = array_map(function($transferBKPNDetail){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_transfer_bkpn' => $transferBKPNDetail['ID_TRANSFER_BKPN'],
                'id_trans_piutang' => $transferBKPNDetail['ID_TRANS_PIUTANG'],
                'id_tahap_pengurusan' => $transferBKPNDetail['ID_TAHAP_PENGURUSAN'],
                'created_by' => $transferBKPNDetail['CREATED_BY'],
                'created_at' => $transferBKPNDetail['CREATED_AT'],
                'updated_by' => $transferBKPNDetail['UPDATED_BY'],
                'updated_at' => $transferBKPNDetail['UPDATED_AT']
            ];
        }, $this->listTransferBKPNDetailFocusPN->toArray());

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
        $this->transferBKPNDetailRepository->deleteByIdSatuanKerjaKPKNLAsal($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_TRANSFER_ARSIP ke TRANSFER_BKPN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransferBKPNDetailFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transferBKPNDetail = new TransferBKPNDetailRepository();
                $transferBKPNDetail->insert($chunk->toArray());
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

    private function getListTransferBKPNDetailModulPengurusan()
    {
        $this->listTransferBKPNDetailModulPengurusan = $this->transferBKPNDetailRepository->getByIdSatuanKerjaKPKNLAsal($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransferBKPNDetailModulPengurusan()
    {
        $this->remappingListTransferBKPNDetailModulPengurusan = array_map(function($transferBKPNDetail){
            return [
                'NOMOR_BA' => $transferBKPNDetail['nomor_ba_penyerahan'],
                'ID_TRANSFER_BKPN_DETAIL' => $transferBKPNDetail['id']
            ];
        }, $this->listTransferBKPNDetailModulPengurusan->toArray());

        return $this;
    }

    private function resyncIdTransferBKPNDetailModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi T_TRANSFER_ARSIP ke TRANSFER_BKPN_DETAIL: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransferBKPNDetailModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tTransferArsip = new TTransferArsipRepository();
                $tTransferArsip->upsert($chunk->toArray(),['NOMOR_BA'],['ID_TRANSFER_BKPN_DETAIL']);
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
