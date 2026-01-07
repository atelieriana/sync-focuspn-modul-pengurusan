<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransKeringananSetujuRepository;
use App\Repositories\FocusPN\TKeringananSetujuRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransKeringananSetujuRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransKeringananSetuju extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 100;

    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransKeringananSetujuRepository $migrasiTransKeringananSetujuRepository;
    private TransKeringananSetujuRepository $transKeringananSetujuRepository;
    private Collection $listTransKeringananSetujuFocusPN;
    private Collection $listTransKeringananSetujuModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingListTransKeringananSetujuFocusPN;
    private array $remappingListTransKeringananSetujuModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransKeringananSetujuRepository = new MigrasiTransKeringananSetujuRepository();
        $this->transKeringananSetujuRepository = new TransKeringananSetujuRepository();
    }
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-keringanan-setuju {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronsasi keringanan setuju';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi keringanan setuju dimulai!');
        $this->setSatuanKerja()
            ->getListTransKeringananSetujuFocusPN()
            ->mappingListTransKeringananSetujuFocusPN()
            ->doSync()
            ->getListTransKeringananSetujuModulPengurusan()
            ->mappingListTransKeringananSetujuModulPengurusan()
            ->doResyncTransKeringananSetujuModulPengurusan();
        $this->info('Sinkronisasi transaksi keringanan setuju selesai');
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

    private function getListTransKeringananSetujuFocusPN()
    {
        $this->listTransKeringananSetujuFocusPN = $this->migrasiTransKeringananSetujuRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransKeringananSetujuFocusPN()
    {
        $this->remappingListTransKeringananSetujuFocusPN = array_map(function ($transKeringananSetuju) {
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_TRANS_PIUTANG' => $transKeringananSetuju['ID_TRANS_PIUTANG'],
                'ID_TRANS_USULAN_KERINGANAN' => $transKeringananSetuju['ID_TRANS_USULAN_KERINGANAN'],
                'ID_TRANS_TAHAP_PERSETUJUAN_KERINGANAN' => $transKeringananSetuju['ID_TRANS_TAHAP_PERSETUJUAN_KERINGANAN'],
                'ID_REF_MATA_UANG' => $transKeringananSetuju['ID_REF_MATA_UANG'],
                'ID_REF_DASAR_KERINGANAN' => $transKeringananSetuju['ID_REF_DASAR_KERINGANAN'],
                'POKOK' => $transKeringananSetuju['POKOK'],
                'BUNGA' => $transKeringananSetuju['BUNGA'],
                'DENDA' => $transKeringananSetuju['DENDA'],
                'LAINNYA' => $transKeringananSetuju['LAINNYA'],
                'BIAD' => $transKeringananSetuju['BIAD'],
                'WAKTU' => $transKeringananSetuju['WAKTU'],
                'TANGGAL_PELUNASAN' => $transKeringananSetuju['TANGGAL_PELUNASAN'],
                'NOMOR_PERMOHONAN' => $transKeringananSetuju['NOMOR_PERMOHONAN'],
                'TANGGAL_PERMOHONAN' => $transKeringananSetuju['TANGGAL_PERMOHONAN'],
                'JANGKA_WAKTU_PENYELESAIAN' => $transKeringananSetuju['JANGKA_WAKTU_PENYELESAIAN'],
                'HAL_PERMOHONAN' => $transKeringananSetuju['HAL_PERMOHONAN'],
                'CP_BARANG_JAMINAN' => $transKeringananSetuju['CP_BARANG_JAMINAN'],
                'CP_KATEGORI_DISKON' => $transKeringananSetuju['CP_KATEGORI_DISKON'],
                'CP_TANGGAL_PERSETUJUAN' => $transKeringananSetuju['CP_TANGGAL_PERSETUJUAN'],
                'CP_DISKON_SALDO_POKOK' => $transKeringananSetuju['CP_DISKON_SALDO_POKOK'],
                'CP_TOTAL_BAYAR_SETELAH_BARJAM' => $transKeringananSetuju['CP_TOTAL_BAYAR_SETELAH_BARJAM'],
                'CP_DISKON_RENCANA_PELUNASAN_1' => $transKeringananSetuju['CP_DISKON_RENCANA_PELUNASAN_1'],
                'CP_DISKON_RENCANA_PELUNASAN_2' => $transKeringananSetuju['CP_DISKON_RENCANA_PELUNASAN_2'],
                'CP_TOTAL_BAYAR_RENCANA_1' => $transKeringananSetuju['CP_TOTAL_BAYAR_RENCANA_1'],
                'CP_TOTAL_BAYAR_RENCANA_2' => $transKeringananSetuju['CP_TOTAL_BAYAR_RENCANA_2'],
                'CP_BIAD_KALKULASI_SISTEM_1' => $transKeringananSetuju['CP_BIAD_KALKULASI_SISTEM_1'],
                'CP_BIAD_KALKULASI_SISTEM_2' => $transKeringananSetuju['CP_BIAD_KALKULASI_SISTEM_2'],
                'ID_REF_BIAD' => $transKeringananSetuju['ID_REF_BIAD'],
                'CP_KATEGORI_DISKON_WAKTU' => $transKeringananSetuju['CP_KATEGORI_DISKON_WAKTU'],
                'REF' => $transKeringananSetuju['REF'],
                'UPDATE_DISKON_CP' => $transKeringananSetuju['UPDATE_DISKON_CP'],
                'FLAG_PKH' => $transKeringananSetuju['FLAG_PKH'],
                'BOBOT_NORMA_WAKTU' => $transKeringananSetuju['BOBOT_NORMA_WAKTU'],
                'TANGGAL_PELUNASAN_PEMBAYARAN' => $transKeringananSetuju['TANGGAL_PELUNASAN_PEMBAYARAN'],
                'CREATED_BY' => $transKeringananSetuju['CREATED_BY'],
                'CREATED_AT' => $transKeringananSetuju['CREATED_AT'],
                'UPDATED_BY' => $transKeringananSetuju['UPDATED_BY'],
                'UPDATED_AT' => $transKeringananSetuju['UPDATED_AT']
            ];
        }, $this->listTransKeringananSetujuFocusPN->toArray());
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
        $this->transKeringananSetujuRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_KERINGANAN_SETUJU ke TRANS_KERINGANAN_SETUJU: ');

        $this->database::beginTransaction();
        try 
        {
            $chunkData = collect($this->remappingListTransKeringananSetujuFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk) 
            {
                $transKeringananSetuju = new TransKeringananSetujuRepository();
                $transKeringananSetuju->insert($chunk->toArray());
                $progressBar->advance();
            }

            $this->database::commit();
            $progressBar->finish();
            $this->output->newLine();
        }
        catch (Exception $e) 
        {
            $this->database::rollBack();
            $this->error($e->getMessage());
        }

        return $this;
    }

    private function getListTransKeringananSetujuModulPengurusan()
    {
        $this->listTransKeringananSetujuModulPengurusan = $this->transKeringananSetujuRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransKeringananSetujuModulPengurusan()
    {
        $this->remappingListTransKeringananSetujuModulPengurusan = array_map(function ($transKeringananSetuju) {
            return [
                'ID' => $transKeringananSetuju['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transKeringananSetuju['ID'],
            ];
        }, $this->listTransKeringananSetujuModulPengurusan->toArray());

        return $this;
    }

    private function doResyncTransKeringananSetujuModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_KERINGANAN_SETUJU ke T_KERINGANAN_SETUJU: ');

        $this->database::beginTransaction();
        try 
        {
            $chunkData = collect($this->remappingListTransKeringananSetujuModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk) 
            {
                $tKeringananSetuju = new TKeringananSetujuRepository();
                $tKeringananSetuju->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
                $progressBar->advance();
            }

            $this->database::commit();
            $progressBar->finish();
            $this->output->newLine();
        }
        catch (Exception $e) 
        {
            $this->database::rollBack();
            $this->error($e->getMessage());
        }
        return $this;
    }
}