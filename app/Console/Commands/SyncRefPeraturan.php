<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiRefPeraturanRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
    private Collection $listPeraturan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->migrasiRefPeraturanRepository = new MigrasiRefPeraturanRepository();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi ref peraturan dimulai');

        $this->getListPeraturan();

        $this->info('Sinkronisasi ref peraturan selesai');
    }

    private function getListPeraturan()
    {
        $this->listPeraturan = $this->migrasiRefPeraturanRepository->all();
        return $this;
    }

    private function doSync()
    {

    }

    private function deleteOldData()
    {

    }

    private function saveNewData()
    {

    }
}

