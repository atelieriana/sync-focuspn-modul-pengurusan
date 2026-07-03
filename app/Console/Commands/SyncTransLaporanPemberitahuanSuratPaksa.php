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

class SyncTransLaporanPemberitahuanSuratPaksa extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransLaporanPemberitahuanSuratPaksaRepository $migrasiTransLaporanPemberitahuanSuratPaksaRepository;
    private TransLaporanPemberitahuanSuratPaksaRepository $transLaporanPemberitahuanSuratPaksaRepository;
    private Collection $listTransLaporanPemberitahuanSuratPaksaFocusPN;
    private int $idSatuanKerja;
    private array $remappingListTransLaporanPemberitahuanSuratPaksaFocusPN;

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
    protected $signature = 'sync:trans-laporan-pemberitahuan-surat-paksa {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi laporan pemberitahuan surat paksa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi laporan penyampaian surat paksa dimulai!');
        $this->setSatuanKerja()
            ->getListTransLaporanPemberitahuanSuratPenyitaanFocusPN()
            ->mappingListTransLaporanPemberitahuanSuratPenyitaanFocusPN()
            ->doSync();
        $this->info('Sinkronisasi transaksi laporan penyampaian surat paksa selesai');
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

    private function getListTransLaporanPemberitahuanSuratPenyitaanFocusPN()
    {
        $this->listTransLaporanPemberitahuanSuratPaksaFocusPN = $this->migrasiTransLaporanPemberitahuanSuratPaksaRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransLaporanPemberitahuanSuratPenyitaanFocusPN()
    {
        $this->remappingListTransLaporanPemberitahuanSuratPaksaFocusPN = array_map(function($transLaporanPemberitahuanSuratPaksa){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transLaporanPemberitahuanSuratPaksa['ID_FOCUSPN'],
                'id_trans_piutang' => $transLaporanPemberitahuanSuratPaksa['ID_TRANS_PIUTANG'],
                'id_trans_tahap_pengurusan' => $transLaporanPemberitahuanSuratPaksa['ID_TRANS_TAHAP_PENGURUSAN'],
                'laporan' => $transLaporanPemberitahuanSuratPaksa['LAPORAN'],
                'created_by' => $transLaporanPemberitahuanSuratPaksa['CREATED_BY'],
                'created_at' => $transLaporanPemberitahuanSuratPaksa['CREATED_AT'],
                'updated_by' => $transLaporanPemberitahuanSuratPaksa['UPDATED_BY'],
                'updated_at' => $transLaporanPemberitahuanSuratPaksa['UPDATED_AT']
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
}
