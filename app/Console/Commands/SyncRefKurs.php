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
                'UUID' => Str::uuid()->toString(),
                'ID_MATA_UANG' => $refKurs['ID_MATA_UANG'],
                'NILAI_KURS' => $refKurs['NILAI_KURS'],
                'REVIEWED' => $refKurs['REVIEWED'],
                'SUMBER' => $refKurs['SUMBER'],
                'TANGGAL_KURS' => $refKurs['TANGGAL_KURS'],
                'REVIEWED_BY' => $refKurs['REVIEWED_BY'],
                'REVIEWED_AT' => $refKurs['REVIEWED_AT'],
                'CREATED_BY' => $refKurs['CREATED_BY'],
                'CREATED_AT' => $refKurs['CREATED_AT'],
                'UPDATED_BY' => $refKurs['UPDATED_BY'],
                'UPDATED_AT' => $refKurs['UPDATED_AT'],
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