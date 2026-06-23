<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiRefSaksiRepository;
use App\Repositories\ModulPengurusan\RefSaksiRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncRefSaksi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:ref-saksi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi pejabat PUPN';

    private DB $database;
    private MigrasiRefSaksiRepository $migrasiRefSaksiRepository;
    private RefSaksiRepository $refSaksiRepository;
    private Collection $listSaksi;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->migrasiRefSaksiRepository = new MigrasiRefSaksiRepository();
        $this->refSaksiRepository = new RefSaksiRepository();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi ref saksi dimulai');
        $this->getListSaksi()
            ->doSync();

        $this->info('Sinkronisasi ref saksi selesai');
    }

    private function getListSaksi()
    {
        $this->listSaksi = $this->migrasiRefSaksiRepository->all();
        return $this;
    }

    private function doSync()
    {
        $this->deleteOldData()
            ->saveNewData();
    }

    private function deleteOldData()
    {
        $this->refSaksiRepository->query()->forceDelete();
        return $this;
    }

    private function saveNewData()
    {
        $this->database::beginTransaction();

        try
        {
            foreach ($this->listSaksi as $saksi)
            {
                $refSaksi = new RefSaksiRepository();
                $refSaksi->uuid = Str::uuid();
                $refSaksi->nama_lengkap = $saksi->NAMA_LENGKAP;
                $refSaksi->nip = $saksi->NIP;
                $refSaksi->pangkat = $saksi->PANGKAT;
                $refSaksi->golongan = $saksi->GOLONGAN;
                $refSaksi->jabatan = $saksi->JABATAN;
                $refSaksi->status = $saksi->STATUS == 1;
                $refSaksi->created_by = 'Migrasi FocusPN';
                $refSaksi->updated_by = 'Migrasi FocusPN';
                $refSaksi->save();
            }
            $this->database::commit();
        }
        catch (Exception $e)
        {
            $this->database::rollBack();
            $this->error($e->getMessage());
        }

        return $this;
    }
}
