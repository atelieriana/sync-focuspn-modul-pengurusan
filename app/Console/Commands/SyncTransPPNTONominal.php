<?php

namespace App\Console\Commands;

use Exception;
use App\Repositories\FocusPN\MigrasiTransPPNTONominalRepository;
use App\Repositories\FocusPN\TPPNTONominalRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransPPNTONominalRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransPPNTONominal extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransPPNTONominalRepository $migrasiTransPPNTONominalRepository;
    private TransPPNTONominalRepository $transPPNTONominalRepository;
    private Collection $listTransPPNTONominalFocusPN;
    private Collection $listTransPPNTONominalModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListTransPPNTONominalFocusPN;
    private array $remappingListTransPPNTONominalModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransPPNTONominalRepository = new MigrasiTransPPNTONominalRepository();
        $this->transPPNTONominalRepository = new TransPPNTONominalRepository();
    }
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-ppnto-nominal {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi trans ppnto nominal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi PPNTO dimulai!');
        $this->setSatuanKerja()
            ->getListTransPPNTONominalFocusPN()
            ->mappingListTransPPNTONominalFocusPN()
            ->doSync()
            ->getListTransPPNTONominalModulPengurusan()
            ->mappingListTransPPNTONominalModulPengurusan()
            ->resyncIdTransPPNTONominalModulPengurusan();
        $this->info('Sinkronisasi transaksi PPNTO selesai');
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

    private function getListTransPPNTONominalFocusPN()
    {
        $this->listTransPPNTONominalFocusPN = $this->migrasiTransPPNTONominalRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPPNTONominalFocusPN()
    {
        $this->remappingListTransPPNTONominalFocusPN = array_map(function($transPPNTONominal){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transPPNTONominal['ID_FOCUSPN'],
                'ID_TRANS_PPNTO' => $transPPNTONominal['ID_TRANS_PPNTO'],
                'ID_REF_MATA_UANG' => $transPPNTONominal['ID_REF_MATA_UANG'],
                'NOMINAL' => $transPPNTONominal['NOMINAL'],
                'CREATED_BY' => $transPPNTONominal['CREATED_BY'],
                'CREATED_AT' => $transPPNTONominal['CREATED_AT'],
                'UPDATED_BY' => $transPPNTONominal['UPDATED_BY'],
                'UPDATED_AT' => $transPPNTONominal['UPDATED_AT']
            ];
        }, $this->listTransPPNTONominalFocusPN->toArray());
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
        $this->transPPNTONominalRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_PPNTO_NOMINAL ke TRANS_PPNTO_NOMINAL: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPPNTONominalFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transPPNTONominalRepository = new TransPPNTONominalRepository();
                $transPPNTONominalRepository->insert($chunk->toArray());
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

    private function getListTransPPNTONominalModulPengurusan()
    {
        $this->listTransPPNTONominalModulPengurusan = $this->transPPNTONominalRepository->getyIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPPNTONominalModulPengurusan()
    {
        $this->remappingListTransPPNTONominalModulPengurusan = array_map(function($transPPNTONominal){
            return [
                'ID' => $transPPNTONominal['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transPPNTONominal['ID']
            ];
        }, $this->listTransPPNTONominalModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransPPNTONominalModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_PPNTO_NOMINAL ke T_PPNTO_NOMINAL: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPPNTONominalModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tPPNTONominalRepository = new TPPNTONominalRepository();
                $tPPNTONominalRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
