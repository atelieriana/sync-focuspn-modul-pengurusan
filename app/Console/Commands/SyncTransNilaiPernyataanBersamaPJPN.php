<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransNilaiPernyataanBersamaPJPNRepository;
use App\Repositories\FocusPN\TNilaiPBPJPNRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransNilaiPernyataanBersamaPJPNRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransNilaiPernyataanBersamaPJPN extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransNilaiPernyataanBersamaPJPNRepository $migrasiTransNilaiPernyataanBersamaPJPNRepository;
    private TransNilaiPernyataanBersamaPJPNRepository $transNilaiPernyataanBersamaPJPNRepository;
    private Collection $listTransNilaiPernyataanBersamaPJPNFocusPN;
    private Collection $listTransNilaiPernyataanBersamaPJPNModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListTransNilaiPernyataanBersamaPJPNFocusPN;
    private array $remappingListTransNilaiPernyataanBersamaPJPNModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransNilaiPernyataanBersamaPJPNRepository = new MigrasiTransNilaiPernyataanBersamaPJPNRepository();
        $this->transNilaiPernyataanBersamaPJPNRepository = new TransNilaiPernyataanBersamaPJPNRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-nilai-pernyataan-bersama-pjpn {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi nilai pernyataan bersama PJPN';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi nilai pernyataan bersama pjpn dimulai!');
        $this->setSatuanKerja()
            ->getTransNilaiPernyataanBersamaPJPNFocusPN()
            ->mappingTransNilaiPernyataanBersamaPJPNFocusPN()
            ->doSync()
            ->getListTransNilaiPernyataanBersamaPJPNModulPengurusan()
            ->mappingListTransNilaiPernyataanBersamaPJPNModulPengurusan()
            ->resyncIdTransNilaiPernyataanBersamaPJPNModulPengurusan();
        $this->info('Sinkronisasi transaksi nilai pernyataan bersama pjpn selesai');
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

    private function getTransNilaiPernyataanBersamaPJPNFocusPN()
    {
        $this->listTransNilaiPernyataanBersamaPJPNFocusPN = $this->migrasiTransNilaiPernyataanBersamaPJPNRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingTransNilaiPernyataanBersamaPJPNFocusPN()
    {
        $this->remappingListTransNilaiPernyataanBersamaPJPNFocusPN = array_map(function($transNilaiPernyataanBersama){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transNilaiPernyataanBersama['ID_FOCUSPN'],
                'ID_TRANS_PIUTANG' => $transNilaiPernyataanBersama['ID_TRANS_PIUTANG'],
                'ID_REF_KETERANGAN_PERNYATAAN_BERSAMA_PJPN' => $transNilaiPernyataanBersama['ID_REF_KETERANGAN_PERNYATAAN_BERSAMA_PJPN'],
                'ID_REF_MATA_UANG' => $transNilaiPernyataanBersama['ID_REF_MATA_UANG'],
                'POKOK' => $transNilaiPernyataanBersama['POKOK'],
                'BUNGA' => $transNilaiPernyataanBersama['BUNGA'],
                'DENDA' => $transNilaiPernyataanBersama['DENDA'],
                'LAINNYA' => $transNilaiPernyataanBersama['LAINNYA'],
                'BIAD' => $transNilaiPernyataanBersama['BIAD'],
                'CREATED_BY' => $transNilaiPernyataanBersama['CREATED_BY'],
                'CREATED_AT' => $transNilaiPernyataanBersama['CREATED_AT'],
                'UPDATED_BY' => $transNilaiPernyataanBersama['UPDATED_BY'],
                'UPDATED_AT' => $transNilaiPernyataanBersama['UPDATED_AT']
            ];
        }, $this->listTransNilaiPernyataanBersamaPJPNFocusPN->toArray());
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
        $this->transNilaiPernyataanBersamaPJPNRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_NILAIPBPJPN ke TRANS_NILAI_PERNYATAAN_BERSAMA_PJPN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransNilaiPernyataanBersamaPJPNFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transNilaiPernyataanBersamaPJPNRepository = new TransNilaiPernyataanBersamaPJPNRepository();
                $transNilaiPernyataanBersamaPJPNRepository->insert($chunk->toArray());
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

    private function getListTransNilaiPernyataanBersamaPJPNModulPengurusan()
    {
        $this->listTransNilaiPernyataanBersamaPJPNModulPengurusan = $this->transNilaiPernyataanBersamaPJPNRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this; 
    }

    private function mappingListTransNilaiPernyataanBersamaPJPNModulPengurusan()
    {
        $this->remappingListTransNilaiPernyataanBersamaPJPNModulPengurusan = array_map(function($transNilaiPernyataanBersama){
            return [
                'ID' => $transNilaiPernyataanBersama['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transNilaiPernyataanBersama['ID']
            ];
        }, $this->listTransNilaiPernyataanBersamaPJPNModulPengurusan->toArray());

        return $this;
    }

    private function resyncIdTransNilaiPernyataanBersamaPJPNModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_NILAI_PERNYATAAN_BERSAMA_PJPN ke T_NILAIPBPJPN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransNilaiPernyataanBersamaPJPNModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tNilaiPBPJPNRepository = new TNilaiPBPJPNRepository();
                $tNilaiPBPJPNRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
