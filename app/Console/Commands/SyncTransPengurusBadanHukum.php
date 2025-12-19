<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransPengurusBadanHukumRepository;
use App\Repositories\FocusPN\TPBHRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransPengurusBadanHukumRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransPengurusBadanHukum extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 100;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransPengurusBadanHukumRepository $migrasiTransPengurusBadanHukumRepository;
    private TransPengurusBadanHukumRepository $transPengurusBadanHukumRepository;
    private Collection $listTransPengurusBadanHukumFocusPN;
    private Collection $listTransPengurusBadanHukumModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingListTransPengurusBadanHukumFocusPN;
    private array $remappingListTransPengurusBadanHukumModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransPengurusBadanHukumRepository = new MigrasiTransPengurusBadanHukumRepository();
        $this->transPengurusBadanHukumRepository = new TransPengurusBadanHukumRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-pengurus-badan-hukum {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi debitur trans pengurus badan hukum dimulai!');
        $this->setSatuanKerja()
            ->getListTransPengurusBadanHukumFocusPN()
            ->mappingListTransPengurusBadanHukumFocusPN()
            ->doSync()
            ->getListTransPengurusBadanHukumModulPengurusan()
            ->mappingTransPengurusBadanHukumModulPengurusan()
            ->doResyncIdTransPengurusBadanHukumModulPengurusan();
        $this->info('Sinkronisasi transaksi debitur trans pengurus badan hukum selesai!');
    }

    /**
     * Digunakan untuk melakukan set kode satuan kerja yang akan dilakukan sinkronisasi
     *
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
     * @return $this
     */
    private function getListTransPengurusBadanHukumFocusPN()
    {
        $this->listTransPengurusBadanHukumFocusPN = $this->migrasiTransPengurusBadanHukumRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPengurusBadanHukumFocusPN()
    {
        $this->remappingListTransPengurusBadanHukumFocusPN = array_map(function ($transPengurusBadanHukumFocusPN) {
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transPengurusBadanHukumFocusPN['ID_FOCUSPN'],
                'ID_TRANS_PIUTANG' => $transPengurusBadanHukumFocusPN['ID_TRANS_PIUTANG_MODUL_PENGURUSAN'],
                'NAMA' => $transPengurusBadanHukumFocusPN['NAMA'],
                'JABATAN' => $transPengurusBadanHukumFocusPN['JABATAN'],
                'KTP' => $transPengurusBadanHukumFocusPN['KTP'],
                'NPWP' => $transPengurusBadanHukumFocusPN['NPWP'],
                'KELURAHAN' => $transPengurusBadanHukumFocusPN['KELURAHAN'],
                'RT_RW' => $transPengurusBadanHukumFocusPN['RT_RW'],
                'ALAMAT' => $transPengurusBadanHukumFocusPN['ALAMAT'],
                'CREATED_BY' => $transPengurusBadanHukumFocusPN['CREATED_BY'],
                'CREATED_AT' => $transPengurusBadanHukumFocusPN['CREATED_AT'],
                'UPDATED_BY' => $transPengurusBadanHukumFocusPN['UPDATED_BY'],
                'UPDATED_AT' => $transPengurusBadanHukumFocusPN['UPDATED_AT'],
            ];
        }, $this->listTransPengurusBadanHukumFocusPN->toArray());

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
        $this->transPengurusBadanHukumRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_PBH ke TRANS_PENGURUS_BADAN_HUKUM: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPengurusBadanHukumFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transPengurusBadanHukum = new TransPengurusBadanHukumRepository();
                $transPengurusBadanHukum->insert($chunk->toArray());
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

    private function getListTransPengurusBadanHukumModulPengurusan()
    {
        $this->listTransPengurusBadanHukumModulPengurusan = $this->transPengurusBadanHukumRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingTransPengurusBadanHukumModulPengurusan()
    {
        $this->remappingListTransPengurusBadanHukumModulPengurusan = array_map(function ($transPengurusBadanHukumModul) {
            return [
                'ID' => $transPengurusBadanHukumModul['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transPengurusBadanHukumModul['ID']
            ];
        }, $this->listTransPengurusBadanHukumModulPengurusan->toArray());
        return $this;
    }

    private function doResyncIdTransPengurusBadanHukumModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_PENGURUS_BADAN_HUKUM ke T_PBH: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPengurusBadanHukumModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tPBH = new TPBHRepository();
                $tPBH->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
