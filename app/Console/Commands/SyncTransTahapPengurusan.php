<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransTahapPengurusanRepository;
use App\Repositories\FocusPN\TTahapRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransTahapPengurusanRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransTahapPengurusan extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 100;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransTahapPengurusanRepository $migrasiTransTahapPengurusanRepository;
    private TransTahapPengurusanRepository $transTahapPengurusanRepository;
    private Collection $listTransTahapPengurusanFocusPN;
    private Collection $listTransTahapPengurusanModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingListTransTahapPengurusanFocusPN;
    private array $remamppingListTransTahapPengurusanModulPengurusan;
    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransTahapPengurusanRepository = new MigrasiTransTahapPengurusanRepository();
        $this->transTahapPengurusanRepository = new TransTahapPengurusanRepository();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-tahap-pengurusan {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi tahap pengurusan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi tahap pengurusan!');
        $this->setSatuanKerja()
            ->getListTransTahapPengurusanFocusPN()
            ->mappingListTransTahapPengurusanFocusPN()
            ->doSync()
            ->getListTransTahapPengurusanModulPengurusan()
            ->mappingListTransPiutangModulPengurusan()
            ->doResyncIdTransTahapPengurusanModulPengurusan();
        $this->info('Sinkronisasi transaksi tahap pengurusan');
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


    private function getListTransTahapPengurusanFocusPN()
    {
        $this->listTransTahapPengurusanFocusPN = $this->migrasiTransTahapPengurusanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransTahapPengurusanFocusPN()
    {
        $this->remappingListTransTahapPengurusanFocusPN = array_map(function ($transTahapPengurusan){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transTahapPengurusan['ID_FOCUSPN'],
                'ID_TRANS_PIUTANG' => $transTahapPengurusan['ID_TRANS_PIUTANG_MODUL_PENGURUSAN'],
                'ID_REF_TAHAP_PENGURUSAN' => $transTahapPengurusan['ID_REF_TAHAP_PENGURUSAN'],
                'ID_REF_SIFAT_SURAT' => $transTahapPengurusan['ID_REF_SIFAT_SURAT'],
                'ID_REF_SATUAN_KERJA_KPKNL' => $transTahapPengurusan['ID_REF_SATUAN_KERJA_KPKNL'],
                'NOMOR_TAHAP' => $transTahapPengurusan['NOMOR_TAHAP'],
                'TANGGAL_TAHAP' => $transTahapPengurusan['TANGGAL_TAHAP'],
                'VALIDASI_KANWIL' => $transTahapPengurusan['VALIDASI_KANWIL'],
                'VALIDASI_KANWIL_AT' => $transTahapPengurusan['VALIDASI_KANWIL_AT'],
                'VALIDASI_PUSAT' => $transTahapPengurusan['VALIDASI_PUSAT'],
                'VALIDASI_PUSAT_AT' => $transTahapPengurusan['VALIDASI_PUSAT_AT'],
                'CREATED_BY' => $transTahapPengurusan['CREATED_BY'],
                'CREATED_AT' => $transTahapPengurusan['CREATED_AT'],
                'UPDATED_BY' => $transTahapPengurusan['UPDATED_BY'],
                'UPDATED_AT' => $transTahapPengurusan['UPDATED_AT'],
            ];
        }, $this->listTransTahapPengurusanFocusPN->toArray());
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
        $this->transTahapPengurusanRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_TAHAP ke TRANS_TAHAP_PENGURUSAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransTahapPengurusanFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transTahapPengurusan = new TransTahapPengurusanRepository();
                $transTahapPengurusan->insert($chunk->toArray());
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

    private function getListTransTahapPengurusanModulPengurusan()
    {
        $this->listTransTahapPengurusanModulPengurusan = $this->transTahapPengurusanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPiutangModulPengurusan()
    {
        $this->remamppingListTransTahapPengurusanModulPengurusan = array_map(function ($transTahapPengurusan){
            return [
                'ID' => $transTahapPengurusan['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transTahapPengurusan['ID']
            ];
        }, $this->listTransTahapPengurusanModulPengurusan->toArray());

        return $this;
    }

    private function doResyncIdTransTahapPengurusanModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_TAHAP_PENGURUSAN ke T_TAHAP: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData =  collect($this->remamppingListTransTahapPengurusanModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tTahap = new TTahapRepository();
                $tTahap->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
                $progressBar->advance();
            }

            $this->database::commit();
            $progressBar->finish();
            $this->output->newLine();
        }
        catch (Exception $exception)
        {
            $this->error($exception->getMessage());
            $this->database::rollBack();
        }
    }
}
