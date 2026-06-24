<?php

namespace App\Console\Commands;

use Exception;
use App\Repositories\FocusPN\MigrasiTransPPDTONominalRepository;
use App\Repositories\FocusPN\TPPDTONominalRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransPPDTONominalRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransPPDTONominal extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransPPDTONominalRepository $migrasiTransPPDTONominalRepository;
    private TransPPDTONominalRepository $transPPDTONominalRepository;
    private Collection $listTransPPDTONominalFocusPN;
    private Collection $listTransPPDTONominalModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListTransPPDTONominalFocusPN;
    private array $remappingListTransPPDTONominalModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransPPDTONominalRepository = new MigrasiTransPPDTONominalRepository();
        $this->transPPDTONominalRepository = new TransPPDTONominalRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-ppdto-nominal {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi PPDTO Nominal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi PPDTO dimulai!');
        $this->setSatuanKerja()
            ->getListTransPPDTONominalFocusPN()
            ->mappingListTransPPDTONominalFocusPN()
            ->doSync()
            ->getListTransPPDTONominalModulPengurusan()
            ->mappingListTransPPDTONominalModulPengurusan()
            ->resyncIdTransPPDTONominalModulPengurusan();
        $this->info('Sinkronisasi transaksi PPDTO selesai');
    }

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

    private function getListTransPPDTONominalFocusPN()
    {
        $this->listTransPPDTONominalFocusPN = $this->migrasiTransPPDTONominalRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPPDTONominalFocusPN()
    {
        $this->remappingListTransPPDTONominalFocusPN = array_map(function($transPPDTONominal){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transPPDTONominal['ID_FOCUSPN'],
                'id_trans_ppdto' => $transPPDTONominal['ID_TRANS_PPDTO'],
                'id_ref_mata_uang' => $transPPDTONominal['ID_REF_MATA_UANG'],
                'nominal' => $transPPDTONominal['NOMINAL'],
                'created_by' => $transPPDTONominal['CREATED_BY'],
                'created_at' => $transPPDTONominal['CREATED_AT'],
                'updated_by' => $transPPDTONominal['UPDATED_BY'],
                'updated_at' => $transPPDTONominal['UPDATED_AT']
            ];
        }, $this->listTransPPDTONominalFocusPN->toArray());
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
        $this->transPPDTONominalRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_PPDTO_NOMINAL ke TRANS_PPDTO_NOMINAL: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPPDTONominalFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transPPDTONominalRepository = new TransPPDTONominalRepository();
                $transPPDTONominalRepository->insert($chunk->toArray());
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

    private function getListTransPPDTONominalModulPengurusan()
    {
        $this->listTransPPDTONominalModulPengurusan = $this->transPPDTONominalRepository->getyIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPPDTONominalModulPengurusan()
    {
        $this->remappingListTransPPDTONominalModulPengurusan = array_map(function($transPPDTONominal){
            return [
                'ID' => $transPPDTONominal['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transPPDTONominal['id']
            ];
        }, $this->listTransPPDTONominalModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransPPDTONominalModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_PPDTO_NOMINAL ke T_PPDTO_NOMINAL: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPPDTONominalModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tPPDTONominalRepository = new TPPDTONominalRepository();
                $tPPDTONominalRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
