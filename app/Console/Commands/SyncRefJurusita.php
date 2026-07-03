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
    const STATUS_PEJABAT_JURUSITA_AKTIF = 1;
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
        $this->idSatuanKerja = $this->refSatuanKerjaRepository->getIdSatuanKerjaByKodeSatuanKerja($this->kodeSatuanKerja)->id;
        return $this;
    }

    private function getListJurusita()
    {
        $this->listJurusitaFocusPN = $this->migrasiRefJurusitaRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function remappingListJurusitaFocusPN()
    {
        $this->remappingListJurusitaFocusPN = array_map(function ($refJurusita){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_ref_satuan_kerja_kpknl' => $this->idSatuanKerja,
                'nip' => $refJurusita['NIP_JURUSITA'],
                'nama_lengkap' => $refJurusita['NAMA_JURUSITA'],
                'nomor_sk_pengangkatan' => trim($refJurusita['NOMOR_SK_JURUSITA']),
                'status' => self::STATUS_PEJABAT_JURUSITA_AKTIF,
                'created_by' => 'Migrasi FocusPN',
                'created_at' => Carbon::now(),
                'updated_by' => 'Migrasi FocusPN',
                'updated_at' => Carbon::now(),
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
