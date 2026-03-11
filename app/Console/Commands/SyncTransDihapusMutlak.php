<?php

namespace App\Console\Commands;

use Exception;
use App\Repositories\FocusPN\MigrasiTransDihapusMutlakRepository;
use App\Repositories\FocusPN\TSPTDMHapusRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransDihapusMutlakRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransDihapusMutlak extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransDihapusMutlakRepository $migrasiTransDihapusMutlakRepository;
    private TransDihapusMutlakRepository $transDihapusMutlakRepository;
    private Collection $listTransDihapusMutlakFocusPN;
    private Collection $listTransDihapusMutlakModulPengurusan;
    private int $idSatuanKerjaKPKNL;
    private array $remappingListTransDihapusMutlakFocusPN;
    private array $remappingListTransDihapusMutlakModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransDihapusMutlakRepository = new MigrasiTransDihapusMutlakRepository();
        $this->transDihapusMutlakRepository = new TransDihapusMutlakRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-dihapus-mutlak {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakuka sinkronisasi transaksi dihapus mutlak';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi piutang dihapus mutlak dimulai!');
        $this->setSatuanKerja()
            ->getListTransDihapusMutlakFocusPN()
            ->mappingListTransDihapusMutlakFocusPN()
            ->doSync()
            ->getListTransDihapusMutlakModulPengurusan()
            ->mappingListTransDihapusMutlakModulPengurusan()
            ->resyncIdTransDihapusMutlakModulPengurusan();
        $this->info('Sinkronisasi transaksi piutang dihapus mutlak selesai');
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

    private function getListTransDihapusMutlakFocusPN()
    {
        $this->listTransDihapusMutlakFocusPN = $this->migrasiTransDihapusMutlakRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransDihapusMutlakFocusPN()
    {
        $this->remappingListTransDihapusMutlakFocusPN = array_map(function ($transDihapusMutlak) {
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transDihapusMutlak['ID_FOCUSPN'],
                'ID_TRANS_PIUTANG' => $transDihapusMutlak['ID_TRANS_PIUTANG'],
                'ID_REF_SATUAN_KERJA_KPKNL' => $transDihapusMutlak['ID_REF_SATUAN_KERJA_KPKNL'],
                'ID_REF_JENIS_KEPUTUSAN_PENGHAPUSAN' => $transDihapusMutlak['ID_REF_JENIS_KEPUTUSAN_PENGHAPUSAN'],
                'KEPUTUSAN_PENGHAPUSAN_OLEH' => $transDihapusMutlak['KEPUTUSAN_PENGHAPUSAN_OLEH'],
                'NOMOR_KEPUTUSAN' => $transDihapusMutlak['NOMOR_KEPUTUSAN'],
                'TANGGAL_KEPUTUSAN' => $transDihapusMutlak['TANGGAL_KEPUTUSAN'],
                'PERIHAL_KEPUTUSAN' => $transDihapusMutlak['PERIHAL_KEPUTUSAN'],
                'CREATED_BY' => $transDihapusMutlak['CREATED_BY'],
                'CREATED_AT' => $transDihapusMutlak['CREATED_AT'],
                'UPDATED_BY' => $transDihapusMutlak['UPDATED_BY'],
                'UPDATED_AT' => $transDihapusMutlak['UPDATED_AT']
            ];
        }, $this->listTransDihapusMutlakFocusPN->toArray());
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
        $this->transDihapusMutlakRepository->deleteByIdSatuanKerja($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_SPTDM_HAPUS ke TRANS_DIHAPUS_MUTLAK: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransDihapusMutlakFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transDihapusMutlakRepository = new TransDihapusMutlakRepository();
                $transDihapusMutlakRepository->insert($chunk->toArray());
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

    private function getListTransDihapusMutlakModulPengurusan()
    {
        $this->listTransDihapusMutlakModulPengurusan = $this->transDihapusMutlakRepository->getByIdSatuanKerja($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransDihapusMutlakModulPengurusan()
    {
        $this->remappingListTransDihapusMutlakModulPengurusan = array_map(function($transDihapusMutlak){
            return [
                'ID' => $transDihapusMutlak['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transDihapusMutlak['ID']
            ];
        }, $this->listTransDihapusMutlakModulPengurusan->toArray());
        return $this;
    }

    public function resyncIdTransDihapusMutlakModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_DIHAPUS_MUTLAK ke T_SPTDM_HAPUS: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransDihapusMutlakModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tSPTDMRepository = new TSPTDMHapusRepository();
                $tSPTDMRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
