<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransLaporanPemberitahuanSuratPaksaRepository;
use App\Repositories\FocusPN\TLPSPRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransLaporanPemberitahuanSuratPaksaRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransLaporanPenyampaianSuratPaksa extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransLaporanPemberitahuanSuratPaksaRepository $migrasiTransLaporanPemberitahuanSuratPaksaRepository;
    private TransLaporanPemberitahuanSuratPaksaRepository $transLaporanPemberitahuanSuratPaksaRepository;
    private Collection $listTransLaporanPemberitahuanSuratPaksaFocusPN;
    private Collection $listTransLaporanPemberitahuanSuratPaksaModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListTransLaporanPemberitahuanSuratPaksaFocusPN;
    private array $remappingListTransLaporanPemberitahuanSuratPaksaModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransLaporanPemberitahuanSuratPaksaRepository = new MigrasiTransLaporanPemberitahuanSuratPaksaRepository();
        $this->transLaporanPemberitahuanSuratPaksaRepository = new TransLaporanPemberitahuanSuratPaksaRepository();
    }
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-laporan-penyampaian-surat-paksa {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi laporan penyampaian surat paksa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi laporan penyampaian surat paksa dimulai!');
        $this->setSatuanKerja()
            ->getListTransLaporanPemberitahuanSuratPenyitaanFocusPN()
            ->mappingListTransLaporanPemberitahuanSuratPenyitaanFocusPN()
            ->doSync()
            ->getListTransLaporanPemberitahuanSuratPenyitaanModulPengurusan()
            ->mappingListTransLaporanPemberitahuanSuratPenyitaanModulPengurusan()
            ->resyncIdTransLaporanPemberitahuanSuratPenyitaanModulPengurusan();
        $this->info('Sinkronisasi transaksi laporan penyampaian surat paksa selesai');
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

    private function getListTransLaporanPemberitahuanSuratPenyitaanFocusPN()
    {
        $this->listTransLaporanPemberitahuanSuratPaksaFocusPN = $this->migrasiTransLaporanPemberitahuanSuratPaksaRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransLaporanPemberitahuanSuratPenyitaanFocusPN()
    {
        $this->remappingListTransLaporanPemberitahuanSuratPaksaFocusPN = array_map(function($transLaporanPemberitahuanSuratPaksa){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transLaporanPemberitahuanSuratPaksa['ID_FOCUSPN'],
                'ID_TRANS_PIUTANG' => $transLaporanPemberitahuanSuratPaksa['ID_TRANS_PIUTANG'],
                'ID_TRANS_TAHAP_PENGURUSAN' => $transLaporanPemberitahuanSuratPaksa['ID_TRANS_TAHAP_PENGURUSAN'],
                'LAPORAN' => $transLaporanPemberitahuanSuratPaksa['LAPORAN'],
                'CREATED_BY' => $transLaporanPemberitahuanSuratPaksa['CREATED_BY'],
                'CREATED_AT' => $transLaporanPemberitahuanSuratPaksa['CREATED_AT'],
                'UPDATED_BY' => $transLaporanPemberitahuanSuratPaksa['UPDATED_BY'],
                'UPDATED_AT' => $transLaporanPemberitahuanSuratPaksa['UPDATED_AT']
            ];
        }, $this->listTransLaporanPemberitahuanSuratPaksaFocusPN->toArray());

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
        $this->transLaporanPemberitahuanSuratPaksaRepository->deleteByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_LPSP ke TRANS_LAPORAN_PEMBERITAHUAN_SURAT_PAKSA: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransLaporanPemberitahuanSuratPaksaFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transLaporanPemberitahuanSuratPaksaRepository = new TransLaporanPemberitahuanSuratPaksaRepository();
                $transLaporanPemberitahuanSuratPaksaRepository->insert($chunk->toArray());
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

    private function getListTransLaporanPemberitahuanSuratPenyitaanModulPengurusan()
    {
        $this->listTransLaporanPemberitahuanSuratPaksaModulPengurusan = $this->transLaporanPemberitahuanSuratPaksaRepository->getByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransLaporanPemberitahuanSuratPenyitaanModulPengurusan()
    {
        $this->remappingListTransLaporanPemberitahuanSuratPaksaModulPengurusan = array_map(function($transLaporanPemberitahuanSuratPaksa){
            return [
                'ID' => $transLaporanPemberitahuanSuratPaksa['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transLaporanPemberitahuanSuratPaksa['ID']
            ];
        }, $this->listTransLaporanPemberitahuanSuratPaksaModulPengurusan->toArray());

        return $this;
    }

    private function resyncIdTransLaporanPemberitahuanSuratPenyitaanModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_LAPORAN_PEMBERITAHUAN_SURAT_PAKSA ke T_LPSP: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransLaporanPemberitahuanSuratPaksaModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tLPSPRepository = new TLPSPRepository();
                $tLPSPRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
