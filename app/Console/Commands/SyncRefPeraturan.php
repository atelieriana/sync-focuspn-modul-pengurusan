<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiRefPeraturanRepository;
use App\Repositories\ModulPengurusan\RefPeraturanRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncRefPeraturan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ref-peraturan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk sinkronisasi ref peraturan';

    private DB $database;
    private MigrasiRefPeraturanRepository $migrasiRefPeraturanRepository;
    private RefPeraturanRepository $refPeraturanRepository;
    private Collection $listPeraturan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->migrasiRefPeraturanRepository = new MigrasiRefPeraturanRepository();
        $this->refPeraturanRepository = new RefPeraturanRepository();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi ref peraturan dimulai');

        $this->getListPeraturan()
            ->doSync();

        $this->info('Sinkronisasi ref peraturan selesai');
    }

    private function getListPeraturan()
    {
        $this->listPeraturan = $this->migrasiRefPeraturanRepository->all();
        return $this;
    }

    private function doSync()
    {
        $this->deleteOldData()
            ->saveNewData();
    }

    private function deleteOldData()
    {
        $this->refPeraturanRepository->query()->forceDelete();
        return $this;
    }

    private function saveNewData()
    {
        $this->database::beginTransaction();

        try
        {
            foreach ($this->listPeraturan as $peraturan)
            {
                $refPeraturan = new RefPeraturanRepository();
                $refPeraturan->uuid = Str::uuid();
                $refPeraturan->nomor_peraturan = $peraturan->NOMOR_PERATURAN;
                $refPeraturan->tanggal_peraturan = $peraturan->TANGGAL_PERATURAN;
                $refPeraturan->perihal = $peraturan->PERIHAL ?? '-';
                $refPeraturan->status = $peraturan->STATUS;
                $refPeraturan->created_by = 'Migrasi FocusPN';
                $refPeraturan->updated_by = 'Migrasi FocusPN';
                $refPeraturan->save();
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

