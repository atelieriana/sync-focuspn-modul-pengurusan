<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransAngsuranRepository;
use App\Repositories\FocusPN\TAngsuranRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransAngsuranRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransAngsuran extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 500;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransAngsuranRepository $migrasiTransAngsuranRepository;
    private TransAngsuranRepository $transAngsuranRepository;
    private Collection $listTransAngsuranFocusPN;
    private Collection $listTransAngsuranModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingListTransAngsuranFocusPN;
    private array $remappingListTransAngsuranMdoulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransAngsuranRepository = new MigrasiTransAngsuranRepository();
        $this->transAngsuranRepository = new TransAngsuranRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-angsuran {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi transaksi angsuran';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi angsuran dimulai!');
        $this->setSatuanKerja()
            ->getListTransAngsuranFocusPN()
            ->mappingListTransAngsuranFocusPN()
            ->doSync()
            ->getListTransAngsuranModulPengurusan()
            ->mappingListTransAngsuranModulPengurusan()
            ->doResyncListTransAngsuranModulPengurusan();
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

    public function getListTransAngsuranFocusPN(): static
    {
        $this->listTransAngsuranFocusPN = $this->migrasiTransAngsuranRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    public function mappingListTransAngsuranFocusPN(): static
    {
        $this->remappingListTransAngsuranFocusPN = array_map(function ($transAngsuran){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_trans_piutang' => $transAngsuran['ID_TRANS_PIUTANG_MODUL_PENGURUSAN'],
                'id_ref_jenis_pembayaran' => $transAngsuran['ID_REF_JENIS_PEMBAYARAN'],
                'id_ref_rekening_tujuan_pembayaran' => $transAngsuran['ID_REF_REKENING_TUJUAN_PEMBAYARAN'],
                'id_ref_biad' => $transAngsuran['ID_REF_BIAD'],
                'id_ref_mata_uang' => $transAngsuran['ID_REF_MATA_UANG'],
                'id_ref_satuan_kerja_kpknl' => $transAngsuran['ID_REF_SATUAN_KERJA_KPKNL'],
                'id_focuspn' => $transAngsuran['ID_FOCUSPN'],
                'bank_koresponden' => $transAngsuran['BANK_KORESPONDEN'],
                'nomor_pembayaran' => $transAngsuran['NOMOR_PEMBAYARAN'],
                'tanggal_pembayaran' => $transAngsuran['TANGGAL_PEMBAYARAN'],
                'nomor_credit_nota' => $transAngsuran['NOMOR_CREDIT_NOTA'],
                'tanggal_credit_nota' => $transAngsuran['TANGGAL_CREDIT_NOTA'],
                'nomor_ntpn' => $transAngsuran['NOMOR_NTPN'],
                'tanggal_ntpn' => $transAngsuran['TANGGAL_NTPN'],
                'hak_kreditur' => (float)$transAngsuran['HAK_KREDITUR'],
                'hak_negara' => (float)$transAngsuran['HAK_NEGARA'],
                'lebih_bayar' => (float)$transAngsuran['LEBIH_BAYAR'],
                'lebih_bayar_penyerah_piutang' => (float)$transAngsuran['LEBIH_BAYAR_PENYERAH_PIUTANG'],
                'lebih_bayar_penanggung_hutang' => (float)$transAngsuran['LEBIH_BAYAR_PENANGGUNG_HUTANG'],
                'keterangan' => $transAngsuran['KETERANGAN'],
                'created_by' => $transAngsuran['CREATED_BY'] ?? '-',
                'created_at' => $transAngsuran['CREATED_AT'],
                'updated_by' => $transAngsuran['UPDATED_BY'] ?? '-',
                'updated_at' => $transAngsuran['UPDATED_AT'],
            ];
        }, $this->listTransAngsuranFocusPN->toArray());

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
        $this->transAngsuranRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_ANGSURAN ke TRANS_ANGSURAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransAngsuranFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transAngsuran = new TransAngsuranRepository();
                $transAngsuran->insert($chunk->toArray());
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

    private function getListTransAngsuranModulPengurusan()
    {
        $this->listTransAngsuranModulPengurusan = $this->transAngsuranRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransAngsuranModulPengurusan()
    {
        $this->remappingListTransAngsuranMdoulPengurusan = array_map(function ($transAngsuran) {
            return [
                'ID' => $transAngsuran['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transAngsuran['id'],
            ];
        }, $this->listTransAngsuranModulPengurusan->toArray());

        return $this;
    }

    private function doResyncListTransAngsuranModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_ANGSURAN ke T_ANGSURAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransAngsuranMdoulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tAngsuran = new TAngsuranRepository();
                $tAngsuran->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
