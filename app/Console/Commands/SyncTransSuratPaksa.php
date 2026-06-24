<?php

namespace App\Console\Commands;

use Exception;
use App\Repositories\FocusPN\MigrasiTransSuratPaksaRepository;
use App\Repositories\FocusPN\TSuratPaksaRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransSuratPaksaRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransSuratPaksa extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransSuratPaksaRepository $migrasiTransSuratPaksaRepository;
    private TransSuratPaksaRepository $transSuratPaksaRepository;
    private Collection $listTransSuratPaksaFocusPN;
    private Collection $listTransSuratPaksaModulPengurusan;
    private int $idSatuanKerjaKPKNL;
    private array $remappingListTransSuratPaksaFocusPN;
    private array $remappingListTransSuratPaksaModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransSuratPaksaRepository = new MigrasiTransSuratPaksaRepository();
        $this->transSuratPaksaRepository = new TransSuratPaksaRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-surat-paksa {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkornisasi surat paksa berdasarkan satuan kerja';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi transaksi tahap surat paksa dimulai!');
        $this->setSatuanKerja()
            ->getListTransSuratPaksaFocusPN()
            ->mappingListTransSuratPaksaFocusPN()
            ->doSync()
            ->getListTransSuratPaksaModulPengurusan()
            ->mappingListTransSuratPaksaModulPengurusan()
            ->resyncIdTransSuratPaksaModulPengurusan();
        $this->info('Sinkronisasi transaksi tahap surat paksa selesai!');
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

    private function getListTransSuratPaksaFocusPN()
    {
        $this->listTransSuratPaksaFocusPN = $this->migrasiTransSuratPaksaRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransSuratPaksaFocusPN()
    {
        $this->remappingListTransSuratPaksaFocusPN = array_map(function($transSuratPaksa){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_focuspn' => $transSuratPaksa['ID_FOCUSPN'],
                'id_trans_tahap_pengurusan' => $transSuratPaksa['ID_TRANS_TAHAP_PENGURUSAN'],
                'id_ref_alasan_surat_paksa' => $transSuratPaksa['ID_REF_ALASAN_SURAT_PAKSA'],
                'tanggal_jatuh_tempo' => $transSuratPaksa['TANGGAL_JATUH_TEMPO'],
                'created_by' => $transSuratPaksa['CREATED_BY'],
                'created_at' => $transSuratPaksa['CREATED_AT'],
                'updated_by' => $transSuratPaksa['UPDATED_BY'],
                'updated_at' => $transSuratPaksa['UPDATED_AT']
            ];
        }, $this->listTransSuratPaksaFocusPN->toArray());

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
        $this->transSuratPaksaRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_SURAT_PAKSA ke TRANS_SURAT_PAKSA: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransSuratPaksaFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transSuratPaksaRepository = new TransSuratPaksaRepository();
                $transSuratPaksaRepository->insert($chunk->toArray());
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

    private function getListTransSuratPaksaModulPengurusan()
    {
        $this->listTransSuratPaksaModulPengurusan = $this->transSuratPaksaRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListTransSuratPaksaModulPengurusan()
    {
        $this->remappingListTransSuratPaksaModulPengurusan = array_map(function($transSuratPaksa){
            return [
                'ID' => $transSuratPaksa['id_focuspn'],
                'ID_MODUL_PENGURUSAN' => $transSuratPaksa['id']
            ];
        }, $this->listTransSuratPaksaModulPengurusan->toArray());

        return $this;
    }

    private function resyncIdTransSuratPaksaModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi TRANS_SURAT_PAKSA ke T_SURAT_PAKSA : ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingListTransSuratPaksaModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tSuratPaksaRepository = new TSuratPaksaRepository();
                $tSuratPaksaRepository->upsert($chunk->toArray(),['ID'],['ID_MODUL_PENGURUSAN']);
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
}
