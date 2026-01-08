<?php

namespace App\Console\Commands;

use App\Models\FocusPN\MigrasiRefJurusita;
use App\Repositories\FocusPN\MigrasiRefJurusitaRepository;
use App\Repositories\ModulPengurusan\RefJurusitaRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncRefJurusita extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 500;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiRefJurusitaRepository $migrasiRefJurusitaRepository;
    private RefJurusitaRepository $refJurusitaRepository;
    private Collection $listJurusitaFocusPN;
    private string $kodeSatuanKerja;
    private int $idSatuanKerja;
    private array $remappingListJurusitaFocusPN = [];

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiRefJurusitaRepository = new MigrasiRefJurusitaRepository();
        $this->refJurusitaRepository = new RefJurusitaRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ref-jurusita {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi jurusita berdasarkan kode satuan kerja';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi jurusita berdasarkan kode satuan kerja dimulai');
        $this->setSatuanKerja()
            ->getListJurusita()
            ->remappingListJurusitaFocusPN()
            ->doSync();
        $this->info('Sinkronisasi jurusita selesai');
    }

     /**
     * Digunakan untuk melakukan set kode satuan kerja yang akan dilakukan sinkronisasi
     * @return $this
     */
    private function setSatuanKerja()
    {
        $this->kodeSatuanKerja = $this->argument('kode-satuan-kerja');
        $this->idSatuanKerja = $this->refSatuanKerjaRepository->getIdSatuanKerjaByKodeSatuanKerja($this->kodeSatuanKerja)->ID;
        return $this;
    }

    private function getListJurusita()
    {
        $this->listJurusitaFocusPN = $this->migrasiRefJurusitaRepository->getByIdSatuanKerjaKPKNL($this->kodeSatuanKerja);
        return $this;
    }

    private function remappingListJurusitaFocusPN()
    {
        $this->remappingListJurusitaFocusPN = array_map(function ($refJurusita){
            return [
                'UUID' => Str::uuid()->toString(),
                'ID_REF_SATUAN_KERJA_KPKNL' => $this->idSatuanKerja,
                'NIP' => $refJurusita['NIP_JURUSITA'],
                'NAMA_LENGKAP' => $refJurusita['NAMA_JURUSITA'],
                'NOMOR_SK_PENGANGKATAN' => trim($refJurusita['NOMOR_SK_JURUSITA']),
                'CREATED_BY' => 'Migrasi FocusPN',
                'CREATED_AT' => Carbon::now(),
                'UPDATED_BY' => 'Migrasi FocusPN',
                'UPDATED_AT' => Carbon::now(),
            ];
        }, $this->listJurusitaFocusPN->toArray());
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
        $this->refJurusitaRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi REF_JURUSITA: ');
        
        $this->database::beginTransaction();
        try 
        {
            $chunkData = collect($this->remappingListJurusitaFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $refJurusitaRepository = new RefJurusitaRepository();
                $refJurusitaRepository->insert($chunk->toArray());
                $progressBar->advance();
            }

            $this->database::commit();
            $progressBar->finish();
            $this->output->newLine();
        }
        catch (Exception $e) 
        {
            $this->database::rollBack();
            $this->error($e->getMessage());
        }
        return $this;
    }
}
