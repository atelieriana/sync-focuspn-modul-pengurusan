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
            $this->idSatuanKerja = $satuanKerja->id;
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
                'uuid' => Str::uuid()->toString(),
                'id_ref_satuan_kerja_kpknl' => $transPPDTO['ID_REF_SATUAN_KERJA_KPKNL'],
                'id_ref_satuan_kerja_kreditur' => $transPPDTO['ID_REF_SATUAN_KERJA_KREDITUR'],
                'nomor_ppdto' => $transPPDTO['NOMOR_PPDTO'],
                'tanggal_ppdto' => $transPPDTO['TANGGAL_PPDTO'],
                'nama_debitur' => $transPPDTO['NAMA_DEBITUR'],
                'path_to_file' => $this->cloneFile($transPPDTO['PATH_TO_FILE'], $transPPDTO['CREATED_AT']),
                'validasi_kpknl' => $transPPDTO['VALIDASI_KPKNL'] == 1,
                'validasi_kpknl_by' => $transPPDTO['VALIDASI_KPKNL_BY'],
                'validasi_kpknl_at' => $transPPDTO['VALIDASI_KPKNL_AT'],
                'validasi_kanwil' => $transPPDTO['VALIDASI_KANWIL'] == 1,
                'validasi_kanwil_by' => $transPPDTO['VALIDASI_KANWIL_BY'],
                'validasi_kanwil_at' => $transPPDTO['VALIDASI_KANWIL_AT'],
                'validasi_pusat' => $transPPDTO['VALIDASI_PUSAT'] == 1,
                'validasi_pusat_by' => $transPPDTO['VALIDASI_PUSAT_BY'],
                'validasi_pusat_at' => $transPPDTO['VALIDASI_PUSAT_AT'],
                'created_by' => $transPPDTO['CREATED_BY'],
                'created_at' => $transPPDTO['CREATED_AT'],
                'updated_by' => $transPPDTO['UPDATED_BY'],
                'updated_at' => $transPPDTO['UPDATED_AT'],
                'deleted_by' => $transPPDTO['DELETED_BY'],
                'deleted_at' => $transPPDTO['DELETED_AT'],
                'id_focuspn' => $transPPDTO['ID_FOCUSPN']
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
                'ID' => $transPPDTO['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transPPDTO['id']
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
