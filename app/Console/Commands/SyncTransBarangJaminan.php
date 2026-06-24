<?php

namespace App\Console\Commands;

use Exception;
use App\Repositories\FocusPN\MigrasiTransBarangJaminanRepository;
use App\Repositories\FocusPN\TJaminanRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransBarangJaminanRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransBarangJaminan extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 10;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransBarangJaminanRepository $migrasiTransBarangJaminanRepository;
    private TransBarangJaminanRepository $transBarangJaminanRepository;
    private Collection $listTransBarangJaminanFocusPN;
    private Collection $listTransBarangJaminanModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListTransBarangJaminanFocusPN;
    private array $remappingListTransBarangJaminanModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransBarangJaminanRepository = new MigrasiTransBarangJaminanRepository();
        $this->transBarangJaminanRepository = new TransBarangJaminanRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-barang-jaminan {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi transaksi barang jaminan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mulai proses sinkronisasi transaksi barang jaminan');
        $this->setSatuanKerja()
            ->getlistTransBarangJaminanFocusPN()
            ->mappingListTransBarangJaminanFocusPN()
            ->doSync()
            ->getlistTransBarangJaminanModulPengurusan()
            ->mappingTransBarangJaminanModulPegurusan()
            ->resyncIdTransBarangJaminanModulPengurusan();
        $this->info('Mulai proses sinkronisasi transaksi barang jaminan');
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

    private function getlistTransBarangJaminanFocusPN()
    {
        $this->listTransBarangJaminanFocusPN = $this->migrasiTransBarangJaminanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransBarangJaminanFocusPN()
    {
        $this->remappingListTransBarangJaminanFocusPN = array_map(function($transBarangJaminan){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transBarangJaminan['ID_FOCUSPN'],
                'id_trans_piutang' => $transBarangJaminan['ID_TRANS_PIUTANG'],
                'nilai_barang_jaminan' => $transBarangJaminan['NILAI_BARANG_JAMINAN'],
                'nilai_appraisal' => $transBarangJaminan['NILAI_APPRAISAL'],
                'keterangan' => $transBarangJaminan['KETERANGAN'],
                'created_by' => $transBarangJaminan['CREATED_BY'] ?? '-',
                'created_at' => $transBarangJaminan['CREATED_AT'],
                'updated_by' => $transBarangJaminan['UPDATED_BY'] ?? '-',
                'updated_at' => $transBarangJaminan['UPDATED_AT']
            ];
        }, $this->listTransBarangJaminanFocusPN->toArray());

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
        $this->transBarangJaminanRepository->deleteByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_JAMINAN ke TRANS_BARANG_JAMINAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransBarangJaminanFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transBarangJaminan = new TransBarangJaminanRepository();
                $transBarangJaminan->insert($chunk->toArray());
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

    private function getlistTransBarangJaminanModulPengurusan()
    {
        $this->listTransBarangJaminanModulPengurusan = $this->transBarangJaminanRepository->getByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function mappingTransBarangJaminanModulPegurusan()
    {
        $this->remappingListTransBarangJaminanModulPengurusan = array_map(function($transBarangJaminan){
            return [
                'ID' => $transBarangJaminan['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transBarangJaminan['id']
            ];
        }, $this->listTransBarangJaminanModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransBarangJaminanModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_BARANG_JAMINAN ke T_JAMINAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransBarangJaminanModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tJaminan = new TJaminanRepository();
                $tJaminan->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
