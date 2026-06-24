<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransDasarTerjadinyaPiutangRepository;
use App\Repositories\FocusPN\TDasarHukumRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransDasarTerjadinyaPiutangRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransDasarTerjadinyaPiutang extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 500;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransDasarTerjadinyaPiutangRepository $migrasiTransDasarTerjadinyaPiutangRepository;
    private TransDasarTerjadinyaPiutangRepository $transDasarTerjadinyaPiutangRepository;
    private Collection $listTransDasarTerjadinyaPiutangFocusPN;
    private Collection $listTransDasarTerjadinyaPiutangModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingTransDasarTerjadinyaPiutangFocusPN;
    private array $remappingTransDasarTerjadinyaPiutangModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransDasarTerjadinyaPiutangRepository = new MigrasiTransDasarTerjadinyaPiutangRepository();
        $this->transDasarTerjadinyaPiutangRepository = new TransDasarTerjadinyaPiutangRepository();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-dasar-terjadinya-piutang {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrasi dasar terjadinya piutang dari FocusPN ke Modul Pengurusan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mulai proses sinkronisasi transaksi dasar terjadinya piutang');
        $this->setSatuanKerja()
            ->getListTransDasarTerjadinyaPiutangFocusPN()
            ->mappingListTransDasarTerjadinyaPiutangFocusPN()
            ->doSync()
            ->getListTransDasarTerjadinyaPiutangModulPengurusan()
            ->mappingListTransDasarTerjadinyaPiutangModulPengurusan()
            ->resyncTransDasarTerjadinyaPiutangModulPengurusan();
        $this->info('Proses sinkronisasi transaksi dasar terjadinya piutang selesai');
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

    private function getListTransDasarTerjadinyaPiutangFocusPN()
    {
        $this->listTransDasarTerjadinyaPiutangFocusPN = $this->migrasiTransDasarTerjadinyaPiutangRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransDasarTerjadinyaPiutangFocusPN()
    {
        $this->remappingTransDasarTerjadinyaPiutangFocusPN = array_map(function($transDasarTerjadinyaPiutang){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transDasarTerjadinyaPiutang['ID_FOCUSPN'],
                'id_trans_piutang' => $transDasarTerjadinyaPiutang['ID_TRANS_PIUTANG'],
                'id_ref_satuan_kerja_kpknl' => $transDasarTerjadinyaPiutang['ID_REF_SATUAN_KERJA_KPKNL'],
                'nomor' => $transDasarTerjadinyaPiutang['NOMOR'],
                'uraian' => $transDasarTerjadinyaPiutang['URAIAN'],
                'created_by' => $transDasarTerjadinyaPiutang['CREATED_BY'],
                'created_at' => $transDasarTerjadinyaPiutang['CREATED_AT'],
                'updated_by' => $transDasarTerjadinyaPiutang['UPDATED_BY'],
                'updated_at' => $transDasarTerjadinyaPiutang['UPDATED_AT']
            ];
        }, $this->listTransDasarTerjadinyaPiutangFocusPN->toArray());

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
        $this->transDasarTerjadinyaPiutangRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_DASARHUKUM ke TRANS_DASAR_TERJADNINYA_PIUTANG: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransDasarTerjadinyaPiutangFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transDasarTerjadinyaPiutang = new TransDasarTerjadinyaPiutangRepository();
                $transDasarTerjadinyaPiutang->insert($chunk->toArray());
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

    private function getListTransDasarTerjadinyaPiutangModulPengurusan()
    {
        $this->listTransDasarTerjadinyaPiutangModulPengurusan = $this->transDasarTerjadinyaPiutangRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransDasarTerjadinyaPiutangModulPengurusan()
    {
        $this->remappingTransDasarTerjadinyaPiutangModulPengurusan = array_map(function($transDasarTerjadinyaPiutang){
            return [
                'ID' => $transDasarTerjadinyaPiutang['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transDasarTerjadinyaPiutang['id']
            ];
        }, $this->listTransDasarTerjadinyaPiutangModulPengurusan->toArray());

        return $this;
    }

    private function resyncTransDasarTerjadinyaPiutangModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_DASAR_TERJADNINYA_PIUTANG ke T_DASARHUKUM: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransDasarTerjadinyaPiutangModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tDasarHukum = new TDasarHukumRepository();
                $tDasarHukum->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
