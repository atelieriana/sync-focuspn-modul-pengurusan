<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransPPNTORepository;
use App\Repositories\FocusPN\TPPNTORepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransPPNTORepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncTransPPNTO extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private Storage $storage;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransPPNTORepository $migrasiTransPPNTORepository;
    private TransPPNTORepository $transPPNTORepository;
    private Collection $listTransPPNTOFocusPN;
    private Collection $listTransPPNTOModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListTransPPNTOFocusPN;
    private array $remappingListTransPPNTOModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->storage = new Storage();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransPPNTORepository = new MigrasiTransPPNTORepository();
        $this->transPPNTORepository = new TransPPNTORepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string 
     */
    protected $signature = 'sync:trans-ppnto {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi transaksi PPNTO';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi PPNTO dimulai!');
        $this->setSatuanKerja()
            ->getListTransPPNTOFocusPN()
            ->mappingListTransPPNTOFocusPN()
            ->doSync()
            ->getListTransPPNTOModulPengurusan()
            ->mappingListTransPPNTOModulPengurusan()
            ->resyncIdTransPPNTOModulPengurusan();
        $this->info('Sinkronisasi transaksi PPNTO selesai');
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

    private function getListTransPPNTOFocusPN()
    {
        $this->listTransPPNTOFocusPN = $this->migrasiTransPPNTORepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPPNTOFocusPN()
    {
        $this->remappingListTransPPNTOFocusPN = array_map(function($transPPNTO){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_REF_SATUAN_KERJA_KPKNL' => $transPPNTO['ID_REF_SATUAN_KERJA_KPKNL'],
                'ID_REF_SATUAN_KERJA_KREDITUR' => $transPPNTO['ID_REF_SATUAN_KERJA_KREDITUR'],
                'NOMOR_PPNTO' => $transPPNTO['NOMOR_PPNTO'],
                'TANGGAL_PPNTO' => $transPPNTO['TANGGAL_PPNTO'],
                'NAMA_DEBITUR' => $transPPNTO['NAMA_DEBITUR'],
                'PATH_TO_FILE' => $this->cloneFile($transPPNTO['PATH_TO_FILE'], $transPPNTO['CREATED_AT']),
                'VALIDASI_KPKNL' => $transPPNTO['VALIDASI_KPKNL'],
                'VALIDASI_KPKNL_BY' => $transPPNTO['VALIDASI_KPKNL_BY'],
                'VALIDASI_KPKNL_AT' => $transPPNTO['VALIDASI_KPKNL_AT'],
                'VALIDASI_KANWIL' => $transPPNTO['VALIDASI_KANWIL'],
                'VALIDASI_KANWIL_BY' => $transPPNTO['VALIDASI_KANWIL_BY'],
                'VALIDASI_KANWIL_AT' => $transPPNTO['VALIDASI_KANWIL_AT'],
                'VALIDASI_PUSAT' => $transPPNTO['VALIDASI_PUSAT'],
                'VALIDASI_PUSAT_BY' => $transPPNTO['VALIDASI_PUSAT_BY'],
                'VALIDASI_PUSAT_AT' => $transPPNTO['VALIDASI_PUSAT_AT'],
                'CREATED_BY' => $transPPNTO['CREATED_BY'],
                'CREATED_AT' => $transPPNTO['CREATED_AT'],
                'UPDATED_BY' => $transPPNTO['UPDATED_BY'],
                'UPDATED_AT' => $transPPNTO['UPDATED_AT'],
                'DELETED_BY' => $transPPNTO['DELETED_BY'],
                'DELETED_AT' => $transPPNTO['DELETED_AT'],
                'ID_FOCUSPN' => $transPPNTO['ID_FOCUSPN']
            ];
        }, $this->listTransPPNTOFocusPN->toArray());
        return $this;
    }

    private function cloneFile(string $fileLocation, string $dateCreated)
    {
        $newLocation = 'ppnto/'.date('Y/m/d',strtotime($dateCreated)).'/';
        $filename = str_replace('ppnto/','',$fileLocation);
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
        $this->transPPNTORepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_PPNTO ke TRANS_PPNTO: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPPNTOFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transPPNTORepository = new TransPPNTORepository();
                $transPPNTORepository->insert($chunk->toArray());
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

    private function getListTransPPNTOModulPengurusan()
    {
        $this->listTransPPNTOModulPengurusan = $this->transPPNTORepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPPNTOModulPengurusan()
    {
        $this->remappingListTransPPNTOModulPengurusan = array_map(function ($transPPNTO) {
            return [
                'ID' => $transPPNTO['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transPPNTO['ID']
            ];
        }, $this->listTransPPNTOModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransPPNTOModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_PPNTO ke T_PPNTO: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPPNTOModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tPPNTORepository = new TPPNTORepository();
                $tPPNTORepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
