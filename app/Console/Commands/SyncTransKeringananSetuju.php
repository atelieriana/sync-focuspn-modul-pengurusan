<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransKeringananSetujuRepository;
use App\Repositories\FocusPN\TKeringananSetujuRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransKeringananSetujuRepository;
use Carbon\Carbon;
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
            $this->idSatuanKerja = $satuanKerja->id;
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
                'uuid' => Str::uuid()->toString(),
                'id_trans_piutang' => $transKeringananSetuju['ID_TRANS_PIUTANG'],
                'id_trans_usulan_keringanan' => $transKeringananSetuju['ID_TRANS_USULAN_KERINGANAN'],
                'id_trans_tahap_persetujuan_keringanan' => $transKeringananSetuju['ID_TRANS_TAHAP_PERSETUJUAN_KERINGANAN'],
                'id_ref_mata_uang' => $transKeringananSetuju['ID_REF_MATA_UANG'],
                'id_ref_dasar_keringanan' => $transKeringananSetuju['ID_REF_DASAR_KERINGANAN'],
                'pokok' => $transKeringananSetuju['POKOK'],
                'bunga' => $transKeringananSetuju['BUNGA'],
                'denda' => $transKeringananSetuju['DENDA'],
                'lainnya' => $transKeringananSetuju['LAINNYA'],
                'biad' => $transKeringananSetuju['BIAD'],
                'waktu' => $transKeringananSetuju['WAKTU'],
                'tanggal_pelunasan' => $transKeringananSetuju['TANGGAL_PELUNASAN'],
                'nomor_permohonan' => $transKeringananSetuju['NOMOR_PERMOHONAN'],
                'tanggal_permohonan' => $transKeringananSetuju['TANGGAL_PERMOHONAN'],
                'jangka_waktu_penyelesaian' => $transKeringananSetuju['JANGKA_WAKTU_PENYELESAIAN'],
                'hal_permohonan' => $transKeringananSetuju['HAL_PERMOHONAN'],
                'cp_barang_jaminan' => $transKeringananSetuju['CP_BARANG_JAMINAN'],
                'cp_kategori_diskon' => $transKeringananSetuju['CP_KATEGORI_DISKON'],
                'cp_tanggal_persetujuan' => $transKeringananSetuju['CP_TANGGAL_PERSETUJUAN'],
                'cp_diskon_saldo_pokok' => $transKeringananSetuju['CP_DISKON_SALDO_POKOK'],
                'cp_total_bayar_setelah_barjam' => $transKeringananSetuju['CP_TOTAL_BAYAR_SETELAH_BARJAM'],
                'cp_diskon_rencana_pelunasan_1' => $transKeringananSetuju['CP_DISKON_RENCANA_PELUNASAN_1'],
                'cp_diskon_rencana_pelunasan_2' => $transKeringananSetuju['CP_DISKON_RENCANA_PELUNASAN_2'],
                'cp_total_bayar_rencana_1' => $transKeringananSetuju['CP_TOTAL_BAYAR_RENCANA_1'],
                'cp_total_bayar_rencana_2' => $transKeringananSetuju['CP_TOTAL_BAYAR_RENCANA_2'],
                'cp_biad_kalkulasi_sistem_1' => $transKeringananSetuju['CP_BIAD_KALKULASI_SISTEM_1'],
                'cp_biad_kalkulasi_sistem_2' => $transKeringananSetuju['CP_BIAD_KALKULASI_SISTEM_2'],
                'id_ref_biad' => $transKeringananSetuju['ID_REF_BIAD'],
                'cp_kategori_diskon_waktu' => $transKeringananSetuju['CP_KATEGORI_DISKON_WAKTU'],
                'ref' => $transKeringananSetuju['REF'],
                'update_diskon_cp' => $transKeringananSetuju['UPDATE_DISKON_CP'],
                'flag_pkh' => $transKeringananSetuju['FLAG_PKH'],
                'bobot_norma_waktu' => $transKeringananSetuju['BOBOT_NORMA_WAKTU'],
                'tanggal_pelunasan_pembayaran' => $transKeringananSetuju['TANGGAL_PELUNASAN_PEMBAYARAN'],
                'created_by' => $transKeringananSetuju['CREATED_BY'] ?? '-',
                'created_at' => $transKeringananSetuju['CREATED_AT'] ?? Carbon::parse($transKeringananSetuju['TANGGAL_TAHAP']),
                'updated_by' => $transKeringananSetuju['UPDATED_BY'] ?? '-',
                'updated_at' => $transKeringananSetuju['UPDATED_AT'] ?? Carbon::parse($transKeringananSetuju['TANGGAL_TAHAP'])
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
                'ID' => $transKeringananSetuju['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transKeringananSetuju['id'],
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
