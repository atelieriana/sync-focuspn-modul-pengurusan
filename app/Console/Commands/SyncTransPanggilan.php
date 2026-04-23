<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransPanggilanRepository;
use App\Repositories\FocusPN\TPanggilanRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransPanggilanRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransPanggilan extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 1;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransPanggilanRepository $migrasiTransPanggilanRepository;
    private TransPanggilanRepository $transPanggilanRepository;
    private Collection $listTransPanggilanFocusPN;
    private Collection $listTransPanggilanModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingTransPanggilanFocusPN;
    private array $remappingTransPanggilanModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransPanggilanRepository = new MigrasiTransPanggilanRepository();
        $this->transPanggilanRepository = new TransPanggilanRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-panggilan {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi trans panggilan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi panggilan dimulai!');
        $this->setSatuanKerja()
            ->getListTransPanggilanFocusPN()
            ->mappingListTransPanggilanFocusPN()
            ->doSync()
            ->getListTransPanggilanModulPengurusan()
            ->mappingListTransPanggilanModulPengurusan()
            ->resyncIdTransPanggilanModulPengurusan();
        $this->info('Sinkronisasi transaksi panggilan selesai');
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

    private function getListTransPanggilanFocusPN()
    {
        $this->listTransPanggilanFocusPN = $this->migrasiTransPanggilanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPanggilanFocusPN()
    {
        $this->remappingTransPanggilanFocusPN = array_map(function($transPanggilan){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transPanggilan['ID_FOCUSPN'],
                'ID_TRANS_PIUTANG' => $transPanggilan['ID_TRANS_PIUTANG'],
                'ID_REF_JENIS_PANGGILAN' => $transPanggilan['ID_REF_JENIS_PANGGILAN'],
                'ID_TRANS_TAHAP_PENGURUSAN' => $transPanggilan['ID_TRANS_TAHAP'],
                'HARI' => $transPanggilan['HARI'],
                'WAKTU' => $transPanggilan['PUKUL'],
                'TANGGAL' => $transPanggilan['TANGGAL'],
                'TEMPAT' => $transPanggilan['TEMPAT'],
                'ALAMAT' => $transPanggilan['ALAMAT'],
                'CREATED_BY' => $transPanggilan['CREATED_BY'],
                'CREATED_AT' => $transPanggilan['CREATED_AT'],
                'UPDATED_BY' => $transPanggilan['UPDATED_BY'],
                'UPDATED_AT' => $transPanggilan['UPDATED_AT']
            ];
        }, $this->listTransPanggilanFocusPN->toArray());
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
        $this->transPanggilanRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_PANGGILAN ke TRANS_PANGGILAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransPanggilanFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transPanggilanRepository = new TransPanggilanRepository();
                $transPanggilanRepository->insert($chunk->toArray());
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

    private function getListTransPanggilanModulPengurusan()
    {
        $this->listTransPanggilanModulPengurusan = $this->transPanggilanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPanggilanModulPengurusan()
    {
        $this->remappingTransPanggilanModulPengurusan = array_map(function($transPanggilan){
            return [
                'ID' => $transPanggilan['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transPanggilan['ID']
            ];
        }, $this->listTransPanggilanModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransPanggilanModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_PANGGILAN ke T_PANGGILAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransPanggilanModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tPanggilanRepository = new TPanggilanRepository();
                $tPanggilanRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
