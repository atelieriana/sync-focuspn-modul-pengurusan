<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransKeringananUsulanRepository;
use App\Repositories\FocusPN\TKeringananUsulanRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransKeringananUsulanRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransKeringananUsulan extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 100;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransKeringananUsulanRepository $migrasiTransKeringananUsulanRepository;
    private TransKeringananUsulanRepository $transKeringananUsulanRepository;
    private Collection $listTransKeringananUsulanFocusPN;
    private Collection $listTransKeringananUsulanModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingListTransKeringananUsulanFocusPN;
    private array $remappingListTransKeringananUsulanModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransKeringananUsulanRepository = new MigrasiTransKeringananUsulanRepository();
        $this->transKeringananUsulanRepository = new TransKeringananUsulanRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-keringanan-usulan {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronsasi keringanan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi keringanan usulan dimulai!');
        $this->setSatuanKerja()
            ->getListTransKeringananUsulanFocusPN()
            ->mappingListTransKeringananUsulanFocusPN()
            ->doSync()
            ->getListTransKeringananUsulanModulPengurusan()
            ->mappingListTransKeringananUsulanModulPengurusan()
            ->doResyncIdTransKeringananUsulanModulPengurusan();
        $this->info('Sinkronisasi transaksi keringanan usulan selesai');
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

    private function getListTransKeringananUsulanFocusPN()
    {
        $this->listTransKeringananUsulanFocusPN = $this->migrasiTransKeringananUsulanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransKeringananUsulanFocusPN()
    {
        $this->remappingListTransKeringananUsulanFocusPN = array_map(function ($transKeringananUsulan) {
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transKeringananUsulan['ID_FOCUSPN'],
                'ID_TRANS_PIUTANG' => $transKeringananUsulan['ID_TRANS_PIUTANG_MODUL_PENGURUSAN'],
                'T_BKPN_ID' => $transKeringananUsulan['T_BKPN_ID'],
                'KODE_KPKNL' => $transKeringananUsulan['KODE_KPKNL_FOCUSPN'],
                'SITE_ID' => $transKeringananUsulan['SITE_ID'],
                'R_KERINGANAN_ID' => $transKeringananUsulan['R_KERINGANAN_ID'],
                'R_CARA_BAYAR_ID' => $transKeringananUsulan['R_CARA_BAYAR_ID'],
                'R_MATAUANG_ID' => $transKeringananUsulan['R_MATAUANG_ID'],
                'POKOK' => $transKeringananUsulan['POKOK'],
                'BUNGA' => $transKeringananUsulan['BUNGA'],
                'DENDA' => $transKeringananUsulan['DENDA'],
                'LAINNYA' => $transKeringananUsulan['LAINNYA'],
                'BIAD' => $transKeringananUsulan['BIAD'],
                'WAKTU' => $transKeringananUsulan['WAKTU'],
                'TANGGAL_PELUNASAN' => $transKeringananUsulan['TANGGAL_PELUNASAN'],
                'NOMOR_USULAN' => $transKeringananUsulan['NOMOR_USULAN'],
                'TANGGAL_USULAN' => $transKeringananUsulan['TANGGAL_USULAN'],
                'CREATED_BY' => $transKeringananUsulan['CREATED_BY'],
                'CREATED_AT' => $transKeringananUsulan['CREATED_AT'],
                'UPDATED_BY' => $transKeringananUsulan['UPDATED_BY'],
                'UPDATED_AT' => $transKeringananUsulan['UPDATED_AT'],
            ];
        }, $this->listTransKeringananUsulanFocusPN->toArray());

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
        $this->transKeringananUsulanRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_KERINGANAN_USULAN ke TRANS_KERINGANAN_USULAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransKeringananUsulanFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transKeringananUsulan = new TransKeringananUsulanRepository();
                $transKeringananUsulan->insert($chunk->toArray());
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

    private function getListTransKeringananUsulanModulPengurusan()
    {
        $this->listTransKeringananUsulanModulPengurusan = $this->transKeringananUsulanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransKeringananUsulanModulPengurusan()
    {
        $this->remappingListTransKeringananUsulanModulPengurusan = array_map(function ($transKeringananUsulan) {
            return [
                'ID' => $transKeringananUsulan['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transKeringananUsulan['ID']
            ];
        }, $this->listTransKeringananUsulanModulPengurusan->toArray());

        return $this;
    }

    private function doResyncIdTransKeringananUsulanModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_KERINGANAN_USULAN ke T_KERINGANAN_USULAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransKeringananUsulanModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tKeringananUsulan = new TKeringananUsulanRepository();
                $tKeringananUsulan->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
