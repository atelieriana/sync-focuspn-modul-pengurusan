<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransPenjaminHutangRepository;
use App\Repositories\FocusPN\TDLPHRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransPenjaminHutangRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransPenjaminHutang extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 100;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransPenjaminHutangRepository $migrasiTransPenjaminHutangRepository;
    private TransPenjaminHutangRepository $transPenjaminHutangRepository;
    private Collection $listTransPenjaminHutangFocusPN;
    private Collection $listTransPenjaminHutangModulPengurusan;
    private int $idSatuanKerja = 0;
    private array $remappingListTransPenjaminHutangFocusPN;
    private array $remappingListTransPenjaminHutangModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransPenjaminHutangRepository = new MigrasiTransPenjaminHutangRepository();
        $this->transPenjaminHutangRepository = new TransPenjaminHutangRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-penjamin-hutang {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi trans penjamin hutang';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi penjamin hutang dimulai!');
        $this->setSatuanKerja()
            ->getListTransPenjaminHutangFocusPN()
            ->mappingListTransPenjaminHutangFocusPN()
            ->doSync()
            ->getListTransPenjaminHutangModulPengurusan()
            ->mappingListTransPenjaminHutangModulPengurusan()
            ->doResyncIdTransPenjaminHutangModulPengurusan();
        $this->info('Sinkronisasi transaksi penjamin hutang selesai!');
    }

    /**
     * Digunakan untuk melakukan set kode satuan kerja yang akan dilakukan sinkronisasi
     *
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

    /**
     * Digunakan untuk mendapatkan data trans penjamin piutang pada focuspn
     *
     * @return $this
     */
    private function getListTransPenjaminHutangFocusPN()
    {
        $this->listTransPenjaminHutangFocusPN = $this->migrasiTransPenjaminHutangRepository->getByIdSatuanKerja($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk melakukan remapping penjamin hutang dari focuspn
     *
     * @return $this
     */
    private function mappingListTransPenjaminHutangFocusPN()
    {
        $this->remappingListTransPenjaminHutangFocusPN = array_map(function ($transPenjaminHutang){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_FOCUSPN' => $transPenjaminHutang['ID_FOCUSPN'],
                'ID_TRANS_PIUTANG' => $transPenjaminHutang['ID_TRANS_PIUTANG_MODUL_PENGURUSAN'],
                'ID_REF_JENIS_PENJAMIN_HUTANG' => $transPenjaminHutang['ID_REF_JENIS_PENJAMIN_HUTANG'],
                'NAMA' => $transPenjaminHutang['NAMA'],
                'TELEPON' => $transPenjaminHutang['TELEPON'],
                'KTP' => $transPenjaminHutang['KTP'],
                'NPWP' => $transPenjaminHutang['NPWP'],
                'PASPOR' => $transPenjaminHutang['PASPOR'],
                'KELURAHAN' => $transPenjaminHutang['KELURAHAN'],
                'RT_RW' => $transPenjaminHutang['RT_RW'],
                'ALAMAT' => $transPenjaminHutang['ALAMAT'],
                'CREATED_BY' => $transPenjaminHutang['CREATED_BY'] ?? '-',
                'CREATED_AT' => $transPenjaminHutang['CREATED_AT'],
                'UPDATED_BY' => $transPenjaminHutang['UPDATED_BY'] ?? '-',
                'UPDATED_AT' => $transPenjaminHutang['UPDATED_AT']
            ];
        }, $this->listTransPenjaminHutangFocusPN->toArray());
        return $this;
    }

    /**
     * Digunakan untuk melakukan sinkronisasi trans penjamin hutang
     *
     * @return static
     */
    private function doSync()
    {
        $this->deleteOldData()
            ->saveNewData();

        return $this;
    }

    /**
     * Digunakan utnuk melakukan penghapusan data berdasarkan id satuan kerja kpknl
     *
     * @return $this
     */
    private function deleteOldData()
    {
        $this->transPenjaminHutangRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    /**
     * Digunakan untuk melakukan penyimpanan data
     *
     * @return void
     */
    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_DLPH ke TRANS_PENJAMIN_HUTANG: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPenjaminHutangFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transPenjaminHutang = new TransPenjaminHutangRepository();
                $transPenjaminHutang->insert($chunk->toArray());
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

    /**
     * Digunakan untuk melakukan pengambilan data trans penjamin piutang berdasarkan id satuan kerja kpknl
     *
     * @return $this
     */
    private function getListTransPenjaminHutangModulPengurusan()
    {
        $this->listTransPenjaminHutangModulPengurusan = $this->transPenjaminHutangRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransPenjaminHutangModulPengurusan()
    {
        $this->remappingListTransPenjaminHutangModulPengurusan = array_map(function($transPenjaminHutang) {
            return [
                'ID' => $transPenjaminHutang['ID_FOCUSPN'],
                'ID_MODUL_PENGURUSAN' => $transPenjaminHutang['ID']
            ];
        }, $this->listTransPenjaminHutangModulPengurusan->toArray());

        return $this;
    }

    private function doResyncIdTransPenjaminHutangModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_PENJAMIN_HUTANG ke T_DLPH: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransPenjaminHutangModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tDplh = new TDLPHRepository();
                $tDplh->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
