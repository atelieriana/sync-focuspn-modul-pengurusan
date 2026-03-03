<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransPPDTORepository;
use App\Repositories\FocusPN\TPPDTORepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransPPDTORepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncTransPPDTO extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private Storage $storage;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransPPDTORepository $migrasiTransPPDTORepository;
    private TransPPDTORepository $transPPDTORepository;
    private Collection $listTransPPDTOFocusPN;
    private Collection $listTransPPDTOModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListTransPPDTOFocusPN;
    private array $remappingListTransPPDTOModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->storage = new Storage();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransPPDTORepository = new MigrasiTransPPDTORepository();
        $this->transPPDTORepository = new TransPPDTORepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-ppdto {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi transaksi PPDTO';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi PPDTO dimulai!');
        $this->setSatuanKerja()
            ->getListTransPPDTOFocusPN()
            ->mappingListTransPPDTOFocusPN()
            ->doSync()
            ->getListTransPPDTOModulPengurusan()
            ->mappingListTransPPDTOModulPengurusan()
            ->resyncIdTransPPDTOModulPengurusan();
        $this->info('Sinkronisasi transaksi PPDTO selesai');
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

    private function getListTransPPDTOFocusPN()
    {
        $this->listTransPPDTOFocusPN = $this->migrasiTransPPDTORepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPPDTOFocusPN()
    {
        $this->remappingListTransPPDTOFocusPN = array_map(function($transPPDTO){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_REF_SATUAN_KERJA_KPKNL' => $transPPDTO['ID_REF_SATUAN_KERJA_KPKNL'],
                'ID_REF_SATUAN_KERJA_KREDITUR' => $transPPDTO['ID_REF_SATUAN_KERJA_KREDITUR'],
                'NOMOR_PPDTO' => $transPPDTO['NOMOR_PPDTO'],
                'TANGGAL_PPDTO' => $transPPDTO['TANGGAL_PPDTO'],
                'NAMA_DEBITUR' => $transPPDTO['NAMA_DEBITUR'],
                'PATH_TO_FILE' => $this->cloneFile($transPPDTO['PATH_TO_FILE'], $transPPDTO['CREATED_AT']),
                'VALIDASI_KPKNL' => $transPPDTO['VALIDASI_KPKNL'],
                'VALIDASI_KPKNL_BY' => $transPPDTO['VALIDASI_KPKNL_BY'],
                'VALIDASI_KPKNL_AT' => $transPPDTO['VALIDASI_KPKNL_AT'],
                'VALIDASI_KANWIL' => $transPPDTO['VALIDASI_KANWIL'],
                'VALIDASI_KANWIL_BY' => $transPPDTO['VALIDASI_KANWIL_BY'],
                'VALIDASI_KANWIL_AT' => $transPPDTO['VALIDASI_KANWIL_AT'],
                'VALIDASI_PUSAT' => $transPPDTO['VALIDASI_PUSAT'],
                'VALIDASI_PUSAT_BY' => $transPPDTO['VALIDASI_PUSAT_BY'],
                'VALIDASI_PUSAT_AT' => $transPPDTO['VALIDASI_PUSAT_AT'],
                'CREATED_BY' => $transPPDTO['CREATED_BY'],
                'CREATED_AT' => $transPPDTO['CREATED_AT'],
                'UPDATED_BY' => $transPPDTO['UPDATED_BY'],
                'UPDATED_AT' => $transPPDTO['UPDATED_AT'],
                'DELETED_BY' => $transPPDTO['DELETED_BY'],
                'DELETED_AT' => $transPPDTO['DELETED_AT'],
                'ID_FOCUSPN' => $transPPDTO['ID_FOCUSPN']
            ];
        }, $this->listTransPPDTOFocusPN->toArray());
        return $this;
    }

    private function cloneFile(string $fileLocation, string $dateCreated)
    {
        $newLocation = 'ppdto/'.date('Y/m/d',strtotime($dateCreated)).'/';
        $filename = str_replace('ppdto/','',$fileLocation);
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
        $this->transPPDTORepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_PPDTO ke TRANS_PPDTO: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPPDTOFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transPPDTORepository = new TransPPDTORepository();
                $transPPDTORepository->insert($chunk->toArray());
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

    private function getListTransPPDTOModulPengurusan()
    {
        $this->listTransPPDTOModulPengurusan = $this->transPPDTORepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPPDTOModulPengurusan()
    {
        $this->remappingListTransPPDTOModulPengurusan = array_map(function ($transPPDTO) {
            return [
                'ID' => $transPPDTO['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transPPDTO['ID']
            ];
        }, $this->listTransPPDTOModulPengurusan->toArray());
        return $this;
    }

    private function resyncIdTransPPDTOModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_PPDTO ke T_PPDTO: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPPDTOModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tPPDTORepository = new TPPDTORepository();
                $tPPDTORepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
