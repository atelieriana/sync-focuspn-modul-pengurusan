<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransSuratKeteranganPengembalianRepository;
use App\Repositories\FocusPN\TSKPPNPengembalianRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransSuratKeteranganPengembalianRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransSuratKeteranganPengembalian extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 1;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransSuratKeteranganPengembalianRepository $migrasiTransSuratKeteranganPengembalianRepository;
    private TransSuratKeteranganPengembalianRepository $transSuratKeteranganPengembalianRepository;
    private Collection $listTransSuratKeteranganPengembalianFocusPN;
    private Collection $listTransSuratKeteranganPengembalianModulPengurusan;
    private int $idSatuanKerjaKPKNL;
    private array $remappingTransSuratKeteranganPengembalianFocusPN;
    private array $remappingTransSuratKeteranganPengembalianModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransSuratKeteranganPengembalianRepository = new MigrasiTransSuratKeteranganPengembalianRepository();
        $this->transSuratKeteranganPengembalianRepository = new TransSuratKeteranganPengembalianRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-surat-keterangan-pengembalian {kode-satuan-kerja : kode satuan kerja 6 digit}';

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
        $this->info('Sinkronisasi transaksi tahap surat keterangan pengembalian!');
        $this->setSatuanKerja()
            ->getListTransSuratKeteranganPengembalianFocusPN()
            ->mappingListTransSuratKeteranganPengembalianFocusPN()
            ->doSync()
            ->getListTransSuratKeteranganPengembalianModulPengurusan()
            ->mappingListTransSuratKeteranganPengembalianModulPengurusan()
            ->resyncIdTransSuratKeteranganPengembalianModulPengurusan();
        $this->info('Sinkronisasi transaksi tahap surat keterangan pengembalian');
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

    private function getListTransSuratKeteranganPengembalianFocusPN()
    {
        $this->listTransSuratKeteranganPengembalianFocusPN = $this->migrasiTransSuratKeteranganPengembalianRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransSuratKeteranganPengembalianFocusPN()
    {
        $this->remappingTransSuratKeteranganPengembalianFocusPN = array_map(function($transSuratKeteranganPengembalian){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transSuratKeteranganPengembalian['ID_FOCUSPN'],
                'ID_TRANS_PIUTANG' => $transSuratKeteranganPengembalian['ID_TRANS_PIUTANG'],
                'ID_TAHAP_PENGURUSAN' => $transSuratKeteranganPengembalian['ID_TAHAP_PENGURUSAN'],
                'ID_SATUAN_KERJA_KREDITUR' => $transSuratKeteranganPengembalian['ID_SATUAN_KERJA_KREDITUR'],
                'ALASAN_PENGEMBALIAN' => $transSuratKeteranganPengembalian['ALASAN_PENGEMBALIAN'],
                'CREATED_BY' => $transSuratKeteranganPengembalian['CREATED_BY'],
                'CREATED_AT' => $transSuratKeteranganPengembalian['CREATED_AT'],
                'UPDATED_BY' => $transSuratKeteranganPengembalian['UPDATED_BY'],
                'UPDATED_AT' => $transSuratKeteranganPengembalian['UPDATED_AT']
            ];
        }, $this->listTransSuratKeteranganPengembalianFocusPN->toArray());

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
        $this->transSuratKeteranganPengembalianRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_SKPPN_PENGEMBALIAN ke TRANS_SURAT_KETERANGAN_PENGEMBALIAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransSuratKeteranganPengembalianFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transSuratKeteranganPengembalianRepository = new TransSuratKeteranganPengembalianRepository();
                $transSuratKeteranganPengembalianRepository->insert($chunk->toArray());
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

    private function getListTransSuratKeteranganPengembalianModulPengurusan()
    {
        $this->listTransSuratKeteranganPengembalianModulPengurusan = $this->transSuratKeteranganPengembalianRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransSuratKeteranganPengembalianModulPengurusan()
    {
        $this->remappingTransSuratKeteranganPengembalianModulPengurusan = array_map(function($transSuratKeteranganPengembalian){
            return [
                'ID' => $transSuratKeteranganPengembalian['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transSuratKeteranganPengembalian['ID']
            ];
        }, $this->listTransSuratKeteranganPengembalianModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransSuratKeteranganPengembalianModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_SURAT_KETERANGAN_PENGEMBALIAN ke T_SKPPN_PENGEMBALIAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransSuratKeteranganPengembalianModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tSKPPNPengembalianRepository = new TSKPPNPengembalianRepository();
                $tSKPPNPengembalianRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
