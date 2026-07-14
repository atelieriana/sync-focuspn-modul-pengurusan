<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransNilaiBarangJaminanRepository;
use App\Repositories\FocusPN\TJaminanRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransNilaiBarangJaminanRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncTransNilaiBarangJaminan extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 50;
    const ID_REF_JENIS_PENILAIAN_TAKSIRAN = 1;
    private DB $database;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransNilaiBarangJaminanRepository $migrasiTransNilaiBarangJaminanRepository;
    private TransNilaiBarangJaminanRepository $transNilaiBarangJaminanRepository;
    private Collection $listTransNilaiBarangJaminanFocusPN;
    private Collection $listTransNilaiBarangJaminanModulPengurusan;
    private int $idSatuanKerja;
    private array $remappingTransNilaiBarangJaminanFocusPN;
    private array $remappingTransNilaiBarangJaminanModulPengurusan;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransNilaiBarangJaminanRepository = new MigrasiTransNilaiBarangJaminanRepository();
        $this->transNilaiBarangJaminanRepository = new TransNilaiBarangJaminanRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trans-nilai-barang-jaminan {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk melakukan sinkronisasi transaksi nilai barang jaminan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mulai proses sinkronisasi transaksi nilai barang jaminan');
        $this->setSatuanKerja()
            ->getListTransNilaiBarangJaminanFocusPN()
            ->mappingListTransNilaiBarangJaminanFocusPN()
            ->doSync()
            ->getListTransNilaiBarangJaminanModulPengurusan()
            ->mappingListTransNilaiBarangJaminanModulPengurusan()
            ->doResyncIdTransNilaiPenyerahanPiutangModulPengurusan();
        $this->info('Mulai proses sinkronisasi transaksi nilai barang jaminan');
    }

    /**
     * Digunakan untuk melakukan set kode satuan kerja yang akan dilakukan sinkronisasi
     * @return $this
     */
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

    private function getListTransNilaiBarangJaminanFocusPN()
    {
        $this->listTransNilaiBarangJaminanFocusPN = $this->migrasiTransNilaiBarangJaminanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja);
        return $this;
    }

    private function mappingListTransNilaiBarangJaminanFocusPN()
    {
        $this->remappingTransNilaiBarangJaminanFocusPN = array_map(function ($transNilaiBarangJaminan) {
            return [
                'uuid' => Str::uuid()->toString(),
                'id_ref_jenis_penilaian' => $transNilaiBarangJaminan['ID_REF_JENIS_PENILAIAN'],
                'id_trans_barang_jaminan' => $transNilaiBarangJaminan['ID_BARANG_JAMINAN'],
                'nilai_barang_jaminan' => $transNilaiBarangJaminan['NILAI_JAMINAN'],
                'created_by' => $transNilaiBarangJaminan['CREATED_BY'],
                'created_at' => $transNilaiBarangJaminan['CREATED_AT'],
                'updated_by' => $transNilaiBarangJaminan['UPDATED_BY'],
                'updated_at' => $transNilaiBarangJaminan['UPDATED_AT']
            ];
        }, $this->listTransNilaiBarangJaminanFocusPN->toArray());
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
        $this->transNilaiBarangJaminanRepository->deleteByIdSatuanKerjaKPKNL($this->idSatuanKerja, self::ID_REF_JENIS_PENILAIAN_TAKSIRAN);
        return $this;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi T_JAMINAN ke trans_nilai_barang_jaminan: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransNilaiBarangJaminanFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transNilaiBarangJaminan = new TransNilaiBarangJaminanRepository();
                $transNilaiBarangJaminan->insert($chunk->toArray());
                $progressBar->advance();
            }

            $this->database::commit();
            $progressBar->finish();
            $this->output->newLine();
        }
        catch (\Exception $exception)
        {
            $this->database::rollBack();
            $this->error($exception->getMessage());
        }
    }

    private function getListTransNilaiBarangJaminanModulPengurusan()
    {
        $this->listTransNilaiBarangJaminanModulPengurusan = $this->transNilaiBarangJaminanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerja, self::ID_REF_JENIS_PENILAIAN_TAKSIRAN);
        return $this;
    }

    private function mappingListTransNilaiBarangJaminanModulPengurusan()
    {
        $this->remappingTransNilaiBarangJaminanModulPengurusan = array_map(function ($transNilaiBarangJaminan) {
            return [
                'ID' => $transNilaiBarangJaminan['id_focuspn'],
                'ID_NILAI_JAMINAN' => $transNilaiBarangJaminan['id'],
            ];
        }, $this->listTransNilaiBarangJaminanModulPengurusan->toArray());
        return $this;
    }

    private function doResyncIdTransNilaiPenyerahanPiutangModulPengurusan()
    {
        $this->info('Tahapan sinkonrisasi trans_nilai_barang_jaminan ke T_JAMINAN: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->remappingTransNilaiBarangJaminanModulPengurusan)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $tJaminanRepository = new TJaminanRepository();
                $tJaminanRepository->upsert($chunk->toArray(),['ID'],['ID_NILAI_JAMINAN']);
                $progressBar->advance();
            }

            $this->database::commit();
            $progressBar->finish();
            $this->output->newLine();
        }
        catch (\Exception $exception)
        {
            $this->database::rollBack();
            $this->error($exception->getMessage());
        }
    }
}
