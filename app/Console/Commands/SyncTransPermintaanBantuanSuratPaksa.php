<?php

namespace App\Console\Commands;

use Exception;
use App\Repositories\FocusPN\MigrasiTransPermintaanBantuanSuratPaksaRepository;
use App\Repositories\FocusPN\TPSBDTRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransPermintaanBantuanSuratPaksaRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransPermintaanBantuanSuratPaksa extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransPermintaanBantuanSuratPaksaRepository $migrasiTransPermintaanBantuanSuratPaksaRepository;
    private TransPermintaanBantuanSuratPaksaRepository $transPermintaanBantuanSuratPaksaRepository;
    private Collection $listTransPermintaanBantuanSuratPaksaFocusPN;
    private Collection $listTransPermintaanBantuanSuratPaksaModulPengurusan;
    private int $idSatuanKerjaKPKNL;
    private array $remappingListTransPermintaanBantuanSuratPaksaFocusPN;
    private array $remappingListTransPermintaanBantuanSuratPaksaModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransPermintaanBantuanSuratPaksaRepository = new MigrasiTransPermintaanBantuanSuratPaksaRepository();
        $this->transPermintaanBantuanSuratPaksaRepository = new TransPermintaanBantuanSuratPaksaRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-permintaan-bantuan-surat-paksa {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi trans permintaan bantuan surat paksa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi permintaan bantuan surat paksa dimulai!');
        $this->setSatuanKerja()
            ->getListTransPermintaanBantuanSuratPaksaFocusPN()
            ->mappingListTransPermintaanBantuanSuratPaksaFocusPN()
            ->doSync()
            ->getListTransPermintaanBantuanSuratPaksaModulPengurusan()
            ->mappingListTransPermintaanBantuanSuratPaksaModulPengurusan()
            ->resyncIdTransPermintaanBantuanSuratPaksaModulPengurusan();
        $this->info('Sinkronisasi transaksi permintaan bantuan surat paksa selesai');
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

    private function getListTransPermintaanBantuanSuratPaksaFocusPN()
    {
        $this->listTransPermintaanBantuanSuratPaksaFocusPN = $this->migrasiTransPermintaanBantuanSuratPaksaRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransPermintaanBantuanSuratPaksaFocusPN()
    {
        $this->remappingListTransPermintaanBantuanSuratPaksaFocusPN = array_map(function($transPermintaanBantuanSuratPaksa){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transPermintaanBantuanSuratPaksa['ID_FOCUSPN'],
                'ID_TRANS_PIUTANG' => $transPermintaanBantuanSuratPaksa['ID_TRANS_PIUTANG'],
                'ID_TRANS_TAHAP_PENGURUSAN' => $transPermintaanBantuanSuratPaksa['ID_TRANS_TAHAP_PENGURUSAN'],
                'ID_SATUAN_KERJA_KPKNL_PERBANTUAN' => $transPermintaanBantuanSuratPaksa['ID_SATUAN_KERJA_KPKNL_PERBANTUAN'],
                'CREATED_BY' => $transPermintaanBantuanSuratPaksa['CREATED_BY'],
                'CREATED_AT' => $transPermintaanBantuanSuratPaksa['CREATED_AT'],
                'UPDATED_BY' => $transPermintaanBantuanSuratPaksa['UPDATED_BY'],
                'UPDATED_AT' => $transPermintaanBantuanSuratPaksa['UPDATED_AT']
            ];
        }, $this->listTransPermintaanBantuanSuratPaksaFocusPN->toArray());
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
        $this->transPermintaanBantuanSuratPaksaRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_SPB_SP ke TRANS_PERMINTAAN_BANTUAN_SURAT_PAKSA: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPermintaanBantuanSuratPaksaFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transPermintaanBantuanSuratPaksaRepository = new TransPermintaanBantuanSuratPaksaRepository();
                $transPermintaanBantuanSuratPaksaRepository->insert($chunk->toArray());
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

    private function getListTransPermintaanBantuanSuratPaksaModulPengurusan()
    {
        $this->listTransPermintaanBantuanSuratPaksaModulPengurusan = $this->transPermintaanBantuanSuratPaksaRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransPermintaanBantuanSuratPaksaModulPengurusan()
    {
        $this->remappingListTransPermintaanBantuanSuratPaksaModulPengurusan = array_map(function($transPermintaanBantuanSuratPaksa){
            return [
                'ID' => $transPermintaanBantuanSuratPaksa['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transPermintaanBantuanSuratPaksa['ID']
            ];
        }, $this->listTransPermintaanBantuanSuratPaksaModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransPermintaanBantuanSuratPaksaModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_PERMINTAAN_BANTUAN_SURAT_PAKSA ke T_SPB_SP: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPermintaanBantuanSuratPaksaModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tSPBSPRepository = new TPSBDTRepository();
                $tSPBSPRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
