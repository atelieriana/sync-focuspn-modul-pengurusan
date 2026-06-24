<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransNilaiPenyerahanPiutangRepository;
use App\Repositories\FocusPN\TNilaiPenyerahanRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransNilaiPenyerahanPiutangRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransNilaiPenyerahanPiutang extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 100;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransNilaiPenyerahanPiutangRepository $migrasiTransNilaiPenyerahanPiutangRepository;
    private TransNilaiPenyerahanPiutangRepository $transNilaiPenyerahanPiutangRepository;
    private Collection $listTransNilaiPenyerahanPiutangFocusPN;
    private Collection $listTransNilaiPenyerahanPiutangModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingListTransNilaiPenyerahanPiutangFocusPN;
    private array $remappingListTransNilaiPenyerahanPiutangModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransNilaiPenyerahanPiutangRepository = new MigrasiTransNilaiPenyerahanPiutangRepository();
        $this->transNilaiPenyerahanPiutangRepository = new TransNilaiPenyerahanPiutangRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-nilai-penyerahan-piutang {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi nilai penyerahan piutang';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi nilai penyerahan piutang dimulai!');
        $this->setSatuanKerja()
            ->getListTransNilaiPenyerahanPiutangFocusPN()
            ->mappingListTransNilaiPenyerahanPiutangFocusPN()
            ->doSync()
            ->getListTransNilaiPenyerahanPiutangModulPengurusan()
            ->mappingListTransNilaiPenyerahanPiutangModulPengurusan()
            ->doResyncIdTransNilaiPenyerahanPiutangModulPengurusan();
        $this->info('Sinkronisasi transaksi nilai penyerahan piutang selesai');
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
            $this->idSatuanKerja = $satuanKerja->ID;
        else
            $this->error('Kode satuan kerja tidak ditemukan');
        return $this;
    }

    private function getListTransNilaiPenyerahanPiutangFocusPN()
    {
        $this->listTransNilaiPenyerahanPiutangFocusPN = $this->migrasiTransNilaiPenyerahanPiutangRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransNilaiPenyerahanPiutangFocusPN()
    {
        $this->remappingListTransNilaiPenyerahanPiutangFocusPN = array_map(function ($transNilaiPenyerahanPiutang) {
            return [
                'uuid' => Str::uuid()->toString(),
                'id_trans_piutang' => $transNilaiPenyerahanPiutang['ID_TRANS_PIUTANG_MODUL_PENGURUSAN'],
                'id_ref_mata_uang' => $transNilaiPenyerahanPiutang['ID_REF_MATA_UANG'],
                'id_ref_satuan_kerja_kpknl' => $transNilaiPenyerahanPiutang['ID_REF_SATUAN_KERJA_KPKNL'],
                'id_focuspn' => $transNilaiPenyerahanPiutang['ID_FOCUSPN'],
                'pokok' => $transNilaiPenyerahanPiutang['POKOK'],
                'bunga' => $transNilaiPenyerahanPiutang['BUNGA'],
                'denda' => $transNilaiPenyerahanPiutang['DENDA'],
                'lainnya' => $transNilaiPenyerahanPiutang['LAINNYA'],
                'created_by' => $transNilaiPenyerahanPiutang['CREATED_BY'],
                'created_at' => $transNilaiPenyerahanPiutang['CREATED_AT'],
                'updated_by' => $transNilaiPenyerahanPiutang['UPDATED_BY'],
                'updated_at' => $transNilaiPenyerahanPiutang['UPDATED_AT'],
            ];
        }, $this->listTransNilaiPenyerahanPiutangFocusPN->toArray());

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
        $this->transNilaiPenyerahanPiutangRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_NILAIPENYERAHAN ke TRANS_NILAI_PENYERAHAN_PIUTANG: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransNilaiPenyerahanPiutangFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transNilaiPenyerahanPiutang = new TransNilaiPenyerahanPiutangRepository();
                $transNilaiPenyerahanPiutang->insert($chunk->toArray());
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

    private function getListTransNilaiPenyerahanPiutangModulPengurusan()
    {
        $this->listTransNilaiPenyerahanPiutangModulPengurusan = $this->transNilaiPenyerahanPiutangRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransNilaiPenyerahanPiutangModulPengurusan()
    {
        $this->remappingListTransNilaiPenyerahanPiutangModulPengurusan = array_map(function ($transNilaiPenyerahanPiutang) {
            return [
                'ID' => $transNilaiPenyerahanPiutang['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transNilaiPenyerahanPiutang['id']
            ];
        }, $this->listTransNilaiPenyerahanPiutangModulPengurusan->toArray());

        return $this;
    }

    private function doResyncIdTransNilaiPenyerahanPiutangModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_NILAI_PENYERAHAN_PIUTANG ke T_NILAIPENYERAHAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransNilaiPenyerahanPiutangModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tNilaiPenyerahan = new TNilaiPenyerahanRepository();
                $tNilaiPenyerahan->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
