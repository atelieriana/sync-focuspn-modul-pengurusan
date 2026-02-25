<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransNilaiSebelumPenyerahanRepository;
use App\Repositories\FocusPN\TNilaiSebelumPenyerahanRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransNilaiSebelumPenyerahanRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransNilaiSebelumPenyerahan extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransNilaiSebelumPenyerahanRepository $migrasiTransNilaiSebelumPenyerahanRepository;
    private TransNilaiSebelumPenyerahanRepository $transNilaiSebelumPenyerahanRepository;
    private Collection $listTransNilaiSebelumPenyerahanFocusPN;
    private Collection $listTransNilaiSebelumPenyerahanModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListTransNilaiSebelumPenyerahanFocusPN;
    private array $remappingListTransNilaiSebelumPenyerahanModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransNilaiSebelumPenyerahanRepository = new MigrasiTransNilaiSebelumPenyerahanRepository();
        $this->transNilaiSebelumPenyerahanRepository = new TransNilaiSebelumPenyerahanRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-nilai-sebelum-penyerahan {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi transaksi nilai sebelum penyerahan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi nilai sebelum penyerahan!');
        $this->setSatuanKerja()
            ->getListTransNilaiSebelumPenyerahanFocusPN()
            ->mappingListTransNilaiSebelumPenyerahanFocusPN()
            ->doSync()
            ->getListTransNilaiSebelumPenyerahanModulPengurusan()
            ->mappingListTransNilaiSebelumPenyerahanModulPengurusan()
            ->resyncIdTransNilaiSebelumPenyerahanModulPengurusan();
        $this->info('Sinkronisasi transaksi nilai sebelum penyerahan');
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

    private function getListTransNilaiSebelumPenyerahanFocusPN()
    {
        $this->listTransNilaiSebelumPenyerahanFocusPN = $this->migrasiTransNilaiSebelumPenyerahanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransNilaiSebelumPenyerahanFocusPN()
    {
        $this->remappingListTransNilaiSebelumPenyerahanFocusPN = array_map(function($transNilaiSebelumPenyerahan) {
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transNilaiSebelumPenyerahan['ID_FOCUSPN'],
                'ID_TRANS_PIUTANG' => $transNilaiSebelumPenyerahan['ID_TRANS_PIUTANG'],
                'ID_REF_MATA_UANG' => $transNilaiSebelumPenyerahan['ID_REF_MATA_UANG'],
                'POKOK' => $transNilaiSebelumPenyerahan['POKOK'],
                'BUNGA' => $transNilaiSebelumPenyerahan['BUNGA'],
                'DENDA' => $transNilaiSebelumPenyerahan['DENDA'],
                'LAINNYA' => $transNilaiSebelumPenyerahan['LAINNYA'],
                'CREATED_BY' => $transNilaiSebelumPenyerahan['CREATED_BY'],
                'CREATED_AT' => $transNilaiSebelumPenyerahan['CREATED_AT'],
                'UPDATED_BY' => $transNilaiSebelumPenyerahan['UPDATED_BY'],
                'UPDATED_AT' => $transNilaiSebelumPenyerahan['UPDATED_AT']
            ];
        }, $this->listTransNilaiSebelumPenyerahanFocusPN->toArray());

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
        $this->transNilaiSebelumPenyerahanRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_NILAISEBELUMPENYERAHAN ke TRANS_NILAI_SEBELUM_PENYERAHAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransNilaiSebelumPenyerahanFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transNilaiSebelumPenyerahanRepository = new TransNilaiSebelumPenyerahanRepository();
                $transNilaiSebelumPenyerahanRepository->insert($chunk->toArray());
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

    private function getListTransNilaiSebelumPenyerahanModulPengurusan()
    {
        $this->listTransNilaiSebelumPenyerahanModulPengurusan = $this->transNilaiSebelumPenyerahanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransNilaiSebelumPenyerahanModulPengurusan()
    {
        $this->remappingListTransNilaiSebelumPenyerahanModulPengurusan = array_map(function($transNilaiSebelumPenyerahan){
            return [
                'ID' => $transNilaiSebelumPenyerahan['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transNilaiSebelumPenyerahan['ID']
            ];
        }, $this->listTransNilaiSebelumPenyerahanModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransNilaiSebelumPenyerahanModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_NILAI_SEBELUM_PENYERAHAN ke T_NILAISEBELUMPENYERAHAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransNilaiSebelumPenyerahanModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tNilaiSebelumPenyerahanRepository = new TNilaiSebelumPenyerahanRepository();
                $tNilaiSebelumPenyerahanRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
