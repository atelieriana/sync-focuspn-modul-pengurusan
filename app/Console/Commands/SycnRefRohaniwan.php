<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiRefRohaniwanRepository;
use App\Repositories\ModulPengurusan\RefRohaniwanRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SycnRefRohaniwan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ref-rohaniwan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi ref rohaniwan';

    private DB $database;
    private MigrasiRefRohaniwanRepository $migrasiRefRohaniwanRepository;
    private RefRohaniwanRepository $refRohaniwanRepository;
    private Collection $listRohaniwan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->migrasiRefRohaniwanRepository = new MigrasiRefRohaniwanRepository();
        $this->refRohaniwanRepository = new RefRohaniwanRepository();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi ref rohaniwan dimulai');
        $this->getListRohaniwan()
            ->doSync();

        $this->info('Sinkronisasi ref rohaniwan selesai');
    }

    private function getListRohaniwan()
    {
        $this->listRohaniwan = $this->migrasiRefRohaniwanRepository->all();
        return $this;
    }

    private function doSync()
    {
        $this->deleteOldData()
            ->saveNewData();
    }

    private function deleteOldData()
    {
        $this->refRohaniwanRepository->query()->forceDelete();
        return $this;
    }

    private function saveNewData()
    {
        $this->database::beginTransaction();

        try
        {
            foreach ($this->listRohaniwan as $rohaniwan)
            {
                $refRohaniwan = new RefRohaniwanRepository();
                $refRohaniwan->UUID = Str::uuid();
                $refRohaniwan->NIP = $rohaniwan->NIP;
                $refRohaniwan->NAMA_LENGKAP = $rohaniwan->NAMA_LENGKAP;
                $refRohaniwan->JABATAN = $rohaniwan->JABATAN;
                $refRohaniwan->STATUS = $rohaniwan->STATUS;
                $refRohaniwan->CREATED_BY = 'Migrasi FocusPN';
                $refRohaniwan->UPDATED_BY = 'Migrasi FocusPN';
                $refRohaniwan->save();
            }
            $this->database::commit();
        }
        catch (Exception $e)
        {
            $this->database::rollBack();
            $this->error($e->getMessage());
        }
    }
}
