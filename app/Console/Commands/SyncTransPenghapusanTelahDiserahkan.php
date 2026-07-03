<?php

namespace App\Console\Commands;

use App\Repositories\ModulPengurusan\TransPenghapusanPiutangTelahDiserahkanRepository;
use Exception;
use App\Repositories\FocusPN\MigrasiTransPenghapusanPiutangTelahDiserahkanRepository;
use App\Repositories\FocusPN\TSPTDMHapusRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransPenghapusanTelahDiserahkan extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransPenghapusanPiutangTelahDiserahkanRepository $migrasiTransPenghapusanPiutangTelahDiserahkanRepository;
    private TransPenghapusanPiutangTelahDiserahkanRepository $transPenghapusanPiutangTelahDiserahkanRepository;
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
        $this->migrasiTransPenghapusanPiutangTelahDiserahkanRepository = new MigrasiTransPenghapusanPiutangTelahDiserahkanRepository();
        $this->transPenghapusanPiutangTelahDiserahkanRepository = new TransPenghapusanPiutangTelahDiserahkanRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-penghapusan-telah-diserahkan {kode-satuan-kerja : kode satuan kerja 6 digit}';

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
            $this->idSatuanKerjaKPKNL = $satuanKerja->id;
        else
            $this->error('Kode satuan kerja tidak ditemukan');
        return $this;
    }

    private function getListTransDihapusMutlakFocusPN()
    {
        $this->listTransDihapusMutlakFocusPN = $this->migrasiTransPenghapusanPiutangTelahDiserahkanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransDihapusMutlakFocusPN()
    {
        $this->remappingListTransDihapusMutlakFocusPN = array_map(function ($transDihapusMutlak) {
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transDihapusMutlak['ID_FOCUSPN'],
                'id_trans_piutang' => $transDihapusMutlak['ID_TRANS_PIUTANG'],
                'id_ref_satuan_kerja_kpknl' => $transDihapusMutlak['ID_REF_SATUAN_KERJA_KPKNL'],
                'id_ref_jenis_keputusan_penghapusan' => $transDihapusMutlak['ID_REF_JENIS_KEPUTUSAN_PENGHAPUSAN'],
                'id_ref_jenis_penghapusan' => $transDihapusMutlak['ID_REF_JENIS_PENGHAPUSAN'],
                'nomor_sk_penghapusan' => $transDihapusMutlak['NOMOR_KEPUTUSAN'],
                'tanggal_sk_penghapusan' => $transDihapusMutlak['TANGGAL_KEPUTUSAN'],
                'perihal_sk_penghapusan' => $transDihapusMutlak['PERIHAL_KEPUTUSAN'],
                'created_by' => $transDihapusMutlak['CREATED_BY'],
                'created_at' => $transDihapusMutlak['CREATED_AT'],
                'updated_by' => $transDihapusMutlak['UPDATED_BY'],
                'updated_at' => $transDihapusMutlak['UPDATED_AT']
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
        $this->transPenghapusanPiutangTelahDiserahkanRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
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
                $transPenghapusanPiutangTelahDiserahkanReposity = new TransPenghapusanPiutangTelahDiserahkanRepository();
                $transPenghapusanPiutangTelahDiserahkanReposity->insert($chunk->toArray());
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
        $this->listTransDihapusMutlakModulPengurusan = $this->transPenghapusanPiutangTelahDiserahkanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransDihapusMutlakModulPengurusan()
    {
        $this->remappingListTransDihapusMutlakModulPengurusan = array_map(function($transDihapusMutlak){
            return [
                'ID' => $transDihapusMutlak['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transDihapusMutlak['id']
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
