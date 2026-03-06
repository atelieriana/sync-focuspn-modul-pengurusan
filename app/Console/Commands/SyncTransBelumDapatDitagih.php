<?php

namespace App\Console\Commands;

use Exception;
use App\Repositories\FocusPN\MigrasiTransBelumDapatDitagihRepository;
use App\Repositories\FocusPN\TPSBDTRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransBelumDapatDitagihRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransBelumDapatDitagih extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransBelumDapatDitagihRepository $migrasiTransBelumDapatDitagihRepository;
    private TransBelumDapatDitagihRepository $transBelumDapatDitagihRepository;
    private Collection $listTransBelumDapatDitagihFocusPN;
    private Collection $listTransBelumDapatDitagihModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListTransBelumDapatDitagihFocusPN;
    private array $remappingListTransBelumDapatDitagihModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransBelumDapatDitagihRepository = new MigrasiTransBelumDapatDitagihRepository();
        $this->transBelumDapatDitagihRepository = new TransBelumDapatDitagihRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-belum-dapat-ditagih {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi trans belum dapat ditagih';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi belum dapat ditagih dimulai!');
        $this->setSatuanKerja()
            ->getListTransBelumDapatDitagihFocusPN()
            ->mappingListTransBelumDapatDitagihFocusPN()
            ->doSync()
            ->getListTransBelumDapatDitagihModulPengurusan()
            ->mappingListTransBelumDapatDitagihModulPengurusan()
            ->resyncIdTransBelumDapatDitagih();
        $this->info('Sinkronisasi transaksi belum dapat ditagih selesai');
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

    private function getListTransBelumDapatDitagihFocusPN()
    {
        $this->listTransBelumDapatDitagihFocusPN = $this->migrasiTransBelumDapatDitagihRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransBelumDapatDitagihFocusPN()
    {
        $this->remappingListTransBelumDapatDitagihFocusPN = array_map(function($transBelumDapatDitagih){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_TRANS_PIUTANG' => $transBelumDapatDitagih['ID_TRANS_PIUTANG'],
                'ID_TRANS_TAHAP_PENGURUSAN' => $transBelumDapatDitagih['ID_TRANS_TAHAP_PENGURUSAN'],
                'ID_REF_JENIS_NILAI_BARANG_JAMINAN' => $transBelumDapatDitagih['ID_REF_JENIS_NILAI_BARANG_JAMINAN'],
                'ID_REF_KLAUSUL_PSBDT' => $transBelumDapatDitagih['ID_REF_KLAUSUL_PSBDT'],
                'ID_REF_BIAD' => $transBelumDapatDitagih['ID_REF_BIAD'],
                'CREATED_BY' => $transBelumDapatDitagih['CREATED_BY'],
                'CREATED_AT' => $transBelumDapatDitagih['CREATED_AT'],
                'UPDATED_BY' => $transBelumDapatDitagih['UPDATED_BY'],
                'UPDATED_AT' => $transBelumDapatDitagih['UPDATED_AT']
            ];
        }, $this->listTransBelumDapatDitagihFocusPN->toArray());
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
        $this->transBelumDapatDitagihRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_PSBDT ke TRANS_BELUM_DAPAT_DITAGIH: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransBelumDapatDitagihFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transBelumDapatDitagihRepository = new TransBelumDapatDitagihRepository();
                $transBelumDapatDitagihRepository->insert($chunk->toArray());
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

    private function getListTransBelumDapatDitagihModulPengurusan()
    {
        $this->listTransBelumDapatDitagihModulPengurusan = $this->transBelumDapatDitagihRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransBelumDapatDitagihModulPengurusan()
    {
        $this->remappingListTransBelumDapatDitagihModulPengurusan = array_map(function($transBelumDapatDitagih){
            return [
                'ID' => $transBelumDapatDitagih['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transBelumDapatDitagih['ID']
            ];
        }, $this->listTransBelumDapatDitagihModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransBelumDapatDitagih()
    {
        $this->info('Tahapan sinkonrisasi TRANS_BELUM_DAPAT_DITAGIH ke T_PSBDT: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransBelumDapatDitagihModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tPSBDTRepository = new TPSBDTRepository();
                $tPSBDTRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
