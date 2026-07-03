<?php

namespace App\Console\Commands;

use Exception;
use App\Repositories\FocusPN\MigrasiTransTembusanRepository;
use App\Repositories\FocusPN\TTembusanRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransTembusanRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransTembusan extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 500;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransTembusanRepository $migrasiTransTembusanRepository;
    private TransTembusanRepository $transTembusanRepository;
    private Collection $listTransTembusanFocusPN;
    private Collection $listTransTembusanModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingListTransTembusanFocusPN;
    private array $remappingListTransTembusanModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransTembusanRepository = new MigrasiTransTembusanRepository();
        $this->transTembusanRepository = new TransTembusanRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-tembusan {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronsasi transaksi tembusan berdasarkan kode KPKNL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi tahap tembusan!');
        $this->setSatuanKerja()
            ->getListTransTembusanFocusPN()
            ->mappingListTransTembusanFocusPN()
            ->doSync()
            ->getListTransTembusanModulPengurusan()
            ->mappingListTransTembusanModulPengurusan()
            ->resyncTransTembusanModulPengurusan();
        $this->info('Sinkronisasi transaksi tahap tembusan');
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

    private function getListTransTembusanFocusPN()
    {
        $this->listTransTembusanFocusPN = $this->migrasiTransTembusanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransTembusanFocusPN()
    {
        $this->remappingListTransTembusanFocusPN = array_map(function($transTembusan){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transTembusan['ID_FOCUSPN'],
                'id_trans_piutang' => $transTembusan['ID_TRANS_PIUTANG'],
                'id_ref_satuan_kerja_kpknl' => $transTembusan['ID_REF_SATUAN_KERJA_KPKNL'],
                'id_tahap_pengurusan' => $transTembusan['ID_TAHAP_PENGURUSAN'],
                'nomor_urut' => $transTembusan['NOMOR_URUT'],
                'created_by' => $transTembusan['CREATED_BY'],
                'created_at' => $transTembusan['CREATED_AT'],
                'updated_by' => $transTembusan['UPDATED_BY'],
                'updated_at' => $transTembusan['UPDATED_AT']
            ];
        }, $this->listTransTembusanFocusPN->toArray());

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
        $this->transTembusanRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_TEMBUSAN ke TRANS_TEMBUSAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransTembusanFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transTembusan = new TransTembusanRepository();
                $transTembusan->insert($chunk->toArray());
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

    private function getListTransTembusanModulPengurusan()
    {
        $this->listTransTembusanModulPengurusan = $this->transTembusanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransTembusanModulPengurusan()
    {
        $this->remappingListTransTembusanModulPengurusan = array_map(function($transTembusan){
            return [
                'ID' => $transTembusan['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transTembusan['id']
            ];
        }, $this->listTransTembusanModulPengurusan->toArray());

        return $this;
    }

    private function resyncTransTembusanModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_TEMBUSAN ke T_TEMBUSAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData =  collect($this->remappingListTransTembusanModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tTembusan = new TTembusanRepository();
                $tTembusan->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
