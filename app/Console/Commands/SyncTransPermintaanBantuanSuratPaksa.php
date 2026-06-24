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
            $this->idSatuanKerjaKPKNL = $satuanKerja->id;
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
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transPermintaanBantuanSuratPaksa['ID_FOCUSPN'],
                'id_trans_piutang' => $transPermintaanBantuanSuratPaksa['ID_TRANS_PIUTANG'],
                'id_trans_tahap_pengurusan' => $transPermintaanBantuanSuratPaksa['ID_TRANS_TAHAP_PENGURUSAN'],
                'id_satuan_kerja_kpknl_perbantuan' => $transPermintaanBantuanSuratPaksa['ID_SATUAN_KERJA_KPKNL_PERBANTUAN'],
                'created_by' => $transPermintaanBantuanSuratPaksa['CREATED_BY'],
                'created_at' => $transPermintaanBantuanSuratPaksa['CREATED_AT'],
                'updated_by' => $transPermintaanBantuanSuratPaksa['UPDATED_BY'],
                'updated_at' => $transPermintaanBantuanSuratPaksa['UPDATED_AT']
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
                'ID' => $transPermintaanBantuanSuratPaksa['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transPermintaanBantuanSuratPaksa['id']
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
