<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransUploadDokumenPengurusanRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransUploadDokumenPengurusanRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\HttpCache\Store;

class SyncUploadedFileTahapan extends Command
{
    private DB $database;
    private Storage $storage;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransUploadDokumenPengurusanRepository $migrasiTransUploadDokumenPengurusanRepository;
    private TransUploadDokumenPengurusanRepository $transUploadDokumenPengurusanRepository;
    private Collection $listUploadedFileTahapanFocusPN;
    private int $idSatuanKerjaKPKNL;
    private array $remappingListUploadedFileTahapanFocusPN;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->storage = new Storage();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransUploadDokumenPengurusanRepository = new MigrasiTransUploadDokumenPengurusanRepository();
        $this->transUploadDokumenPengurusanRepository = new TransUploadDokumenPengurusanRepository();
    }
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:upload-file {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi uploaded file tahapan dimulai!');
        $this->setSatuanKerja()
            ->getListUploadedFileTahapanFocusPN()
            ->mappingListUploadedFileTahapanFocusPN();
        $this->info('Sinkronisasi uploaded file tahapan selesai');
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

    private function getListUploadedFileTahapanFocusPN()
    {
        $this->listUploadedFileTahapanFocusPN = $this->migrasiTransUploadDokumenPengurusanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListUploadedFileTahapanFocusPN()
    {
        $this->listUploadedFileTahapanFocusPN = array_map(function($uploadedFile){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_TRANS_TAHAP_PENGURUSAN' => $uploadedFile['ID_TRANS_TAHAP_PENGURUSAN'],
                'PATH_TO_FILE' => $this->transferFileToMinio($pathToFile),
                'CREATED_BY' => $uploadedFile['CREATED_BY'],
                'CREATED_AT' => $uploadedFile['CREATED_AT'],
                'UPDATED_BY' => $uploadedFile['UPDATED_BY'],
                'UPDATED_AT' => $uploadedFile['UPDATED_AT']
            ];
        }, $this->listUploadedFileTahapanFocusPN->toArray());
    }

    private function transferFileToMinio(string $pathToFile)
    {
        return $this;
    }
}