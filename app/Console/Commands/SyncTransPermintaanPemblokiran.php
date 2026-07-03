<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransPermintaanPemblokiranRepository;
use App\Repositories\FocusPN\TPermintaanBlokirRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransPermintaanPemblokiranRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransPermintaanPemblokiran extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransPermintaanPemblokiranRepository $migrasiTransPermintaanPemblokiranRepository;
    private TransPermintaanPemblokiranRepository $transPermintaanPemblokiranRepository;
    private Collection $listTransPermintaanPemblokiranFocusPN;
    private Collection $listTransPermintaanPemblokiranModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListTransPermintaanPemblokiranFocusPN;
    private array $remappingListTransPermintaanPemblokiranModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransPermintaanPemblokiranRepository = new MigrasiTransPermintaanPemblokiranRepository();
        $this->transPermintaanPemblokiranRepository = new TransPermintaanPemblokiranRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-permintaan-pemblokiran {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi transaksi permintaan pemblokiran barang jaminan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi permintaan pemblokiran barang jaminan dimulai!');
        $this->setSatuanKerja()
            ->getListTransPermintaanPemblokiranFocusPN()
            ->mappingListTransPermintaanPemblokiranFocusPN()
            ->doSync()
            ->getListTransPermintaanPemblokiranModulPengurusan()
            ->mappingListTransPermintaanPemblokiranModulPengurusan()
            ->resyncIdTransPermintaanPemblokiranModulPengurusan();
        $this->info('Sinkronisasi transaksi permintaan pemblokiran barang jaminan selesai');
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

    private function getListTransPermintaanPemblokiranFocusPN()
    {
        $this->listTransPermintaanPemblokiranFocusPN = $this->migrasiTransPermintaanPemblokiranRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPermintaanPemblokiranFocusPN()
    {
        $this->remappingListTransPermintaanPemblokiranFocusPN = array_map(function($transPermintaanPemblokiran){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transPermintaanPemblokiran['ID_FOCUSPN'],
                'id_trans_piutang' => $transPermintaanPemblokiran['ID_TRANS_PIUTANG'],
                'id_trans_barang_jaminan' => $transPermintaanPemblokiran['ID_TRANS_BARANG_JAMINAN'],
                'id_trans_tahap_pengurusan' => $transPermintaanPemblokiran['ID_TRANS_TAHAP_PENGURUSAN'],
                'tujuan_surat' => $transPermintaanPemblokiran['TUJUAN_SURAT'],
                'alamat' => $transPermintaanPemblokiran['ALAMAT'],
                'created_by' => $transPermintaanPemblokiran['CREATED_BY'],
                'created_at' => $transPermintaanPemblokiran['CREATED_AT'],
                'updated_by' => $transPermintaanPemblokiran['UPDATED_BY'],
                'updated_at' => $transPermintaanPemblokiran['UPDATED_AT']
            ];
        }, $this->listTransPermintaanPemblokiranFocusPN->toArray());
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
        $this->transPermintaanPemblokiranRepository->deleteByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_PERMINTAAN_BLOKIR ke TRANS_PERMINTAAN_PEMBLOKIRAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPermintaanPemblokiranFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transPermintaanPemblokiranRepository = new TransPermintaanPemblokiranRepository();
                $transPermintaanPemblokiranRepository->insert($chunk->toArray());
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

    private function getListTransPermintaanPemblokiranModulPengurusan()
    {
        $this->listTransPermintaanPemblokiranModulPengurusan = $this->transPermintaanPemblokiranRepository->getByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPermintaanPemblokiranModulPengurusan()
    {
        $this->remappingListTransPermintaanPemblokiranModulPengurusan = array_map(function($transPermintaanPemblokiran){
            return [
                'ID' => $transPermintaanPemblokiran['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transPermintaanPemblokiran['id']
            ];
        }, $this->listTransPermintaanPemblokiranModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransPermintaanPemblokiranModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_PERMINTAAN_PEMBLOKIRAN ke T_PERMINTAAN_BLOKIR: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPermintaanPemblokiranModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tPermintaanBlokirRepository = new TPermintaanBlokirRepository();
                $tPermintaanBlokirRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
        return $this;
    }
}
