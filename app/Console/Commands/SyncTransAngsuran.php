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
            $this->idSatuanKerja = $satuanKerja->ID;
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
                'UUID' => Str::uuid()->toString(),
                'ID_TRANS_PIUTANG' => $transAngsuran['ID_TRANS_PIUTANG_MODUL_PENGURUSAN'],
                'ID_REF_JENIS_PEMBAYARAN' => $transAngsuran['ID_REF_JENIS_PEMBAYARAN'],
                'ID_REF_REKENING_TUJUAN_PEMBAYARAN' => $transAngsuran['ID_REF_REKENING_TUJUAN_PEMBAYARAN'],
                'ID_REF_BIAD' => $transAngsuran['ID_REF_BIAD'],
                'ID_REF_MATA_UANG' => $transAngsuran['ID_REF_MATA_UANG'],
                'ID_REF_SATUAN_KERJA_KPKNL' => $transAngsuran['ID_REF_SATUAN_KERJA_KPKNL'],
                'ID_FOCUSPN' => $transAngsuran['ID_FOCUSPN'],
                'BANK_KORESPONDEN' => $transAngsuran['BANK_KORESPONDEN'],
                'NOMOR_PEMBAYARAN' => $transAngsuran['NOMOR_PEMBAYARAN'],
                'TANGGAL_PEMBAYARAN' => $transAngsuran['TANGGAL_PEMBAYARAN'],
                'NOMOR_CREDIT_NOTA' => $transAngsuran['NOMOR_CREDIT_NOTA'],
                'TANGGAL_CREDIT_NOTA' => $transAngsuran['TANGGAL_CREDIT_NOTA'],
                'NOMOR_NTPN' => $transAngsuran['NOMOR_NTPN'],
                'TANGGAL_NTPN' => $transAngsuran['TANGGAL_NTPN'],
                'HAK_KREDITUR' => $transAngsuran['HAK_KREDITUR'],
                'HAK_NEGARA' => $transAngsuran['HAK_NEGARA'],
                'LEBIH_BAYAR' => $transAngsuran['LEBIH_BAYAR'],
                'LEBIH_BAYAR_PENYERAH_PIUTANG' => $transAngsuran['LEBIH_BAYAR_PENYERAH_PIUTANG'],
                'LEBIH_BAYAR_PENANGGUNG_HUTANG' => $transAngsuran['LEBIH_BAYAR_PENANGGUNG_HUTANG'],
                'KETERANGAN' => $transAngsuran['KETERANGAN'],
                'CREATED_BY' => $transAngsuran['CREATED_BY'] ?? '-',
                'CREATED_AT' => $transAngsuran['CREATED_AT'],
                'UPDATED_BY' => $transAngsuran['UPDATED_BY'] ?? '-',
                'UPDATED_AT' => $transAngsuran['UPDATED_AT'],
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
        $this->info('Tahapan sinkonrisasi T_GLOBAL ke TRANS_PIUTANG: ');

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
                'ID' => $transAngsuran['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transAngsuran['ID'],
            ];
        }, $this->listTransAngsuranModulPengurusan->toArray());

        return $this;
    }

    private function doResyncListTransAngsuranModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi T_GLOBAL ke TRANS_PIUTANG: ');

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
