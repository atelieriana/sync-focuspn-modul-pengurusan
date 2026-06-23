<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiRefKursRepository;
use App\Repositories\ModulPengurusan\RefKursRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncRefKurs extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 500;
    private DB $database;
    private MigrasiRefKursRepository $migrasiRefKursRepository;
    private RefKursRepository $refKursRepository;
    private Collection $listKurs;
    private array $remappingListKursFocusPN;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->migrasiRefKursRepository = new MigrasiRefKursRepository();
        $this->refKursRepository = new RefKursRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ref-kurs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi ref kurs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi ref kurs dimulai');

        $this->getListKurs()
            ->mappingKursFocusPN()
            ->doSync();

        $this->info('Sinkronisasi ref kurs selesai');
    }

    private function getListKurs()
    {
        $this->listKurs = $this->migrasiRefKursRepository->all();
        return $this;
    }

    private function mappingKursFocusPN()
    {
        $this->remappingListKursFocusPN = array_map(function($refKurs){
            return [
                'uuid' => Str::uuid()->toString(),
                'id_mata_uang' => $refKurs['ID_MATA_UANG'],
                'nilai_kurs' => $refKurs['NILAI_KURS'],
                'reviewed' => $refKurs['REVIEWED'] == 1,
                'sumber' => $refKurs['SUMBER'],
                'tanggal_kurs' => $refKurs['TANGGAL_KURS'],
                'reviewed_by' => $refKurs['REVIEWED_BY'],
                'reviewed_at' => $refKurs['REVIEWED_AT'],
                'created_by' => $refKurs['CREATED_BY'],
                'created_at' => $refKurs['CREATED_AT'],
                'updated_by' => $refKurs['UPDATED_BY'],
                'updated_at' => $refKurs['UPDATED_AT'],
            ];
        }, $this->listKurs->toArray());

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
        $this->refKursRepository->truncate();
        return $this;
    }

    private function saveNewData()
    {
        $this->database::beginTransaction();

        try
        {
            $chunkData = collect($this->remappingListKursFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $refKurs = new RefKursRepository();
                $refKurs->insert($chunk->toArray());
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
