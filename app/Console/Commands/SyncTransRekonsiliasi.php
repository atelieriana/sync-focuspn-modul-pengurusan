<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransRekonsiliasiRepository;
use App\Repositories\FocusPN\TRekonsiliasiRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransRekonsiliasiRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncTransRekonsiliasi extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    const DIRECTORY_MINIO = 'rekonsiliasi';
    private DB $database;
    private Storage $storage;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransRekonsiliasiRepository $migrasiTransRekonsiliasiRepository;
    private TransRekonsiliasiRepository $transRekonsiliasiRepository;
    private Collection $listTransRekonsiliasiFocusPN;
    private Collection $listTransRekonsiliasiModulPengurusan;
    private int $idSatuanKerjaKPKNL;
    private array $remappingTransRekonsiliasiFocusPN;
    private array $remappingTransRekonsiliasiModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->storage = new Storage();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransRekonsiliasiRepository = new MigrasiTransRekonsiliasiRepository();
        $this->transRekonsiliasiRepository = new TransRekonsiliasiRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-rekonsiliasi {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi transaksi rekonsiliasi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi rekonsiliasi dimulai!');
        $this->setSatuanKerja()
            ->getListTransRekonsiliasiFocusPN()
            ->mappingListTransRekonsiliasiFocusPN()
            ->doSync()
            ->getListTransRekonsiliasiModulPengurusan()
            ->mappingListTransRekonsiliasiModulPengurusan()
            ->resyncIdTransRekonsiliasiModulPengurusan();
        $this->info('Sinkronisasi transaksi rekonsiliasi selesai');
    }

    private function setSatuanKerja()
    {
        $kodeSatuanKerja = $this->argument('kode-satuan-kerja');
        $satuanKerja = $this->refSatuanKerjaRepository->getIdSatuanKerjaByKodeSatuanKerja($kodeSatuanKerja);
        if (!is_null($satuanKerja))
            $this->idSatuanKerjaKPKNL = $satuanKerja->ID;
        else
            $this->error('Kode satuan kerja tidak ditemukan');
        return $this;
    }

    private function getListTransRekonsiliasiFocusPN()
    {
        $this->listTransRekonsiliasiFocusPN = $this->migrasiTransRekonsiliasiRepository->getIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransRekonsiliasiFocusPN()
    {
        $this->remappingTransRekonsiliasiFocusPN = array_map(function($transRekonsiliasi){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transRekonsiliasi['ID_FOCUSPN'],
                'ID_REF_SATUAN_KERJA_KPKNL' => $transRekonsiliasi['ID_REF_SATUAN_KERJA_KPKNL'],
                'ID_REF_SATUAN_KERJA_KREDITUR' => $transRekonsiliasi['ID_REF_SATUAN_KERJA_KREDITUR'],
                'TAHUN' => $transRekonsiliasi['TAHUN'],
                'PERIODE' => $transRekonsiliasi['PERIODE'],
                'NOMOR_BAR_PENYERAH_PIUTANG' => $transRekonsiliasi['NOMOR_BAR_PENYERAH_PIUTANG'],
                'NOMOR_BAR_KPKNL' => $transRekonsiliasi['NOMOR_BAR_KPKNL'],
                'TANGGAL_REKON' => $transRekonsiliasi['TANGGAL_REKON'],
                'PATH_TO_FILE' => $this->cloneFile($transRekonsiliasi['PATH_TO_FILE'], $transRekonsiliasi['CREATED_AT']),
                'VALIDASI_KPKNL' => $transRekonsiliasi['VALIDASI_KPKNL'],
                'VALIDASI_KPKNL_BY' => $transRekonsiliasi['VALIDASI_KPKNL_BY'],
                'VALIDASI_KPKNL_AT' => $transRekonsiliasi['VALIDASI_KPKNL_AT'],
                'VALIDASI_KANWIL' => $transRekonsiliasi['VALIDASI_KANWIL'],
                'VALIDASI_KANWIL_BY' => $transRekonsiliasi['VALIDASI_KANWIL_BY'],
                'VALIDASI_KANWIL_AT' => $transRekonsiliasi['VALIDASI_KANWIL_AT'],
                'VALIDASI_PUSAT' => $transRekonsiliasi['VALIDASI_PUSAT'],
                'VALIDASI_PUSAT_BY' => $transRekonsiliasi['VALIDASI_PUSAT_BY'],
                'VALIDASI_PUSAT_AT' => $transRekonsiliasi['VALIDASI_PUSAT_AT'],
                'ALASAN_REJECT' => $transRekonsiliasi['ALASAN_REJECT'],
                'CREATED_BY' => $transRekonsiliasi['CREATED_BY'],
                'CREATED_AT' => $transRekonsiliasi['CREATED_AT'],
                'UPDATED_BY' => $transRekonsiliasi['UPDATED_BY'],
                'UPDATED_AT' => $transRekonsiliasi['UPDATED_AT'],
            ];
        }, $this->listTransRekonsiliasiFocusPN->toArray());
        return $this;
    }

    private function cloneFile(string $fileLocation, string $dateCreated)
    {
        $newLocation = self::DIRECTORY_MINIO.'/'.date('Y/m/d',strtotime($dateCreated)).'/';
        $filename = str_replace('rekonsiliasi/','',$fileLocation);
        $fileContent = $this->storage::disk('s3_focuspn')->get($fileLocation);
        $newFileLocation = $newLocation.$filename;
        $this->storage::disk('s3_modul_pengurusan')->put($newFileLocation, $fileContent);
        return $newFileLocation;
    }

    private function doSync()
    {
        $this->deleteOldData()
            ->saveNewData();
        return $this;
    }

    private function deleteOldData()
    {
        $this->transRekonsiliasiRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_REKONSILIASI ke TRANS_REKONSILIASI: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransRekonsiliasiFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transRekonsiliasiRepository = new TransRekonsiliasiRepository();
                $transRekonsiliasiRepository->insert($chunk->toArray());
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

    private function getListTransRekonsiliasiModulPengurusan()
    {
        $this->listTransRekonsiliasiModulPengurusan = $this->transRekonsiliasiRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransRekonsiliasiModulPengurusan()
    {
        $this->remappingTransRekonsiliasiModulPengurusan = array_map(function($transRekonsiliasi){
            return [
                'ID' => $transRekonsiliasi['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transRekonsiliasi['ID']
            ];
        }, $this->listTransRekonsiliasiModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransRekonsiliasiModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_REKONSILIASI ke T_REKONSILIASI: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransRekonsiliasiModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tRekonsilasiRepository = new TRekonsiliasiRepository();
                $tRekonsilasiRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
