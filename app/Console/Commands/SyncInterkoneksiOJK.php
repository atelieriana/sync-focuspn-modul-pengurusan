<?php

namespace App\Console\Commands;

use App\Models\FocusPN\TOJK;
use App\Repositories\FocusPN\MigrasiInterkoneksiOJKRepository;
use App\Repositories\ModulPengurusan\InterkoneksiOJKRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncInterkoneksiOJK extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 10;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiInterkoneksiOJKRepository $migrasiInterkoneksiOJKRepository;
    private InterkoneksiOJKRepository $interkoneksiOJKRepository;
    private Collection $listInterkoneksiOJKFocusPN;
    private Collection $listInterkoneksiOJKModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingInterkoneksiOJKFocusPN;
    private array $remappingInterkoneksiOJKModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiInterkoneksiOJKRepository = new MigrasiInterkoneksiOJKRepository();
        $this->interkoneksiOJKRepository = new InterkoneksiOJKRepository();
    }
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:interkoneksi-ojk {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi interkoneksi OJK berdasarkan kode satuan kerja';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi angsuran dimulai!');
        $this->setSatuanKerja()
            ->getListInterkoneksiOJKFocusPN()
            ->mappingListInterkoneksiOJKFocusPN()
            ->doSync()
            ->getListInterkoneksiModulPengurusan()
            ->mappingListInterkoneksiModulPengurusan()
            ->doResyncInterkoneksiOJKModulPengurusan();
        $this->info('Sinkronisasi transaksi angsuran selesai');
    }

    /**
     * Digunakan untuk melakukan set kode satuan kerja yang akan dilakukan sinkronisasi
     * @return $this
     */
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

    private function getListInterkoneksiOJKFocusPN()
    {
        $this->listInterkoneksiOJKFocusPN = $this->migrasiInterkoneksiOJKRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }
    
    private function mappingListInterkoneksiOJKFocusPN()
    {
        $this->remappingInterkoneksiOJKFocusPN = array_map(function($interkoneksiOJK){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_ref_flag_ojk' => $interkoneksiOJK['ID_REF_FLAG_OJK'] ?? null,
                'id_ref_feedback_ojk' => $interkoneksiOJK['ID_REF_FEEDBACK_OJK'] ?? null,
                'id_ref_satuan_kerja_kpknl' => (int) $interkoneksiOJK['ID_REF_SATUAN_KERJA_KPKNL'],
                'id_trans_debitur' => (int) $interkoneksiOJK['ID_TRANS_DEBITUR'],
                'status_berkas_ojk' => $interkoneksiOJK['STATUS_BERKAS_OJK'] == 1,
                'id_focuspn' => $interkoneksiOJK['ID_FOCUSPN'],
                'created_by' => 'Migrasi FocusPN',
                'created_at' => Carbon::now('Asia/Jakarta'),
                'updated_by' => 'Migrasi FocusPN',
                'updated_at' => Carbon::now('Asia/Jakarta')
            ];
        }, $this->listInterkoneksiOJKFocusPN->toArray());

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
        $this->interkoneksiOJKRepository->deleteByIdRefSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_OJK ke interkoneksi_ojk: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingInterkoneksiOJKFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $interkoneksiOJK = new InterkoneksiOJKRepository();
                $interkoneksiOJK->insert($chunk->toArray());
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

    private function getListInterkoneksiModulPengurusan()
    {
        $this->listInterkoneksiOJKModulPengurusan = $this->interkoneksiOJKRepository->getByIdRefSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListInterkoneksiModulPengurusan()
    {
        $this->remappingInterkoneksiOJKModulPengurusan = array_map(function($interkoneksiOJK){
            return [
                'ID' => $interkoneksiOJK['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $interkoneksiOJK['id']
            ];
        }, $this->listInterkoneksiOJKModulPengurusan->toArray());

        return $this;
    }

    private function doResyncInterkoneksiOJKModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi interkoneksi_ojk ke T_OJK: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingInterkoneksiOJKModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tOJK = new TOJK();
                $tOJK->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
