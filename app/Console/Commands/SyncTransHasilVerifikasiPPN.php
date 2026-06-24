<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransHasilVerifikasiPPNRepository;
use App\Repositories\FocusPN\THVPPNRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransHasilVerifikasiPPNRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransHasilVerifikasiPPN extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 500;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransHasilVerifikasiPPNRepository $migrasiTransHasilVerifikasiPPNRepository;
    private TransHasilVerifikasiPPNRepository $transHasilVerifikasiPPNRepository;
    private Collection $listTransHasilVerifikasiPPNFocusPN;
    private Collection $listTransHasilVerifikasiPPNModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListTransHasilVerifikasiPPNFocusPN;
    private array $remappingListTransHasilVerifikasiPPNModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransHasilVerifikasiPPNRepository = new MigrasiTransHasilVerifikasiPPNRepository();
        $this->transHasilVerifikasiPPNRepository = new TransHasilVerifikasiPPNRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-hasil-verifikasi-ppn {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi hasil verifikasi ppn';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi hasil verifikasi PPN!');
        $this->setSatuanKerja()
            ->getListTransHasilVerifikasiPPNFocusPN()
            ->mappingListTransHasilVerifikasiPPNFocusPN()
            ->doSync()
            ->getListTransHasilVerifikasiPPNModulPengurusan()
            ->mappingListTransHasilVerifikasiPPNModulPengurusan()
            ->resyncIdTransHasilVerifikasiPPN();
        $this->info('Sinkronisasi transaksi hasil verifikasi PPN selesai');
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

    private function getListTransHasilVerifikasiPPNFocusPN()
    {
        $this->listTransHasilVerifikasiPPNFocusPN = $this->migrasiTransHasilVerifikasiPPNRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransHasilVerifikasiPPNFocusPN()
    {
        $this->remappingListTransHasilVerifikasiPPNFocusPN = array_map(function($transHasilVerifikasiPPN){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transHasilVerifikasiPPN['ID_FOCUSPN'],
                'id_trans_piutang' => $transHasilVerifikasiPPN['ID_TRANS_PIUTANG'],
                'id_tahap_pengurusan' => $transHasilVerifikasiPPN['ID_TAHAP_PENGURUSAN'],
                'id_ref_alasan_verifikasi_ppn' => $transHasilVerifikasiPPN['ID_REF_ALASAN_VERIFIKASI_PPN'],
                'nomor_nd_permintaan' => $transHasilVerifikasiPPN['NOMOR_ND_PERMINTAAN'],
                'tanggal_nd_permintaan' => $transHasilVerifikasiPPN['TANGGAL_ND_PERMINTAAN'],
                'created_by' => $transHasilVerifikasiPPN['CREATED_BY'],
                'created_at' => $transHasilVerifikasiPPN['CREATED_AT'],
                'updated_by' => $transHasilVerifikasiPPN['UPDATED_BY'],
                'updated_at' => $transHasilVerifikasiPPN['UPDATED_AT']
            ];
        }, $this->listTransHasilVerifikasiPPNFocusPN->toArray());

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
        $this->transHasilVerifikasiPPNRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_HVPPN ke TRANS_HASIL_VERIFIKASI_PPN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransHasilVerifikasiPPNFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transHasilVerifikasiPPN = new TransHasilVerifikasiPPNRepository();
                $transHasilVerifikasiPPN->insert($chunk->toArray());
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

    private function getListTransHasilVerifikasiPPNModulPengurusan()
    {
        $this->listTransHasilVerifikasiPPNModulPengurusan = $this->transHasilVerifikasiPPNRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransHasilVerifikasiPPNModulPengurusan()
    {
        $this->remappingListTransHasilVerifikasiPPNModulPengurusan = array_map(function($transHasilVerifikasiPPN){
            return [
                'ID' => $transHasilVerifikasiPPN['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transHasilVerifikasiPPN['id']
            ];
        }, $this->listTransHasilVerifikasiPPNModulPengurusan->toArray());

        return $this;
    }

    private function resyncIdTransHasilVerifikasiPPN()
    {
        $this->info('Tahapan sinkonrisasi TRANS_HASIL_VERIFIKASI_PPN ke T_HVPPN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransHasilVerifikasiPPNModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tHVPPN = new THVPPNRepository();
                $tHVPPN->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
