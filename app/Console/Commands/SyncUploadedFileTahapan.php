<?php

namespace App\Console\Commands;

use App\Repositories\FocusPN\MigrasiTransUploadDokumenPengurusanRepository;
use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use App\Repositories\ModulPengurusan\TransUploadDokumenPengurusanRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncUploadedFileTahapan extends Command
{
    const TOTAL_DATA_EACH_CHUNK = 500;
    private DB $database;
    private Storage $storage;
    private RefSatuanKerjaRepository $refSatuanKerjaRepository;
    private MigrasiTransUploadDokumenPengurusanRepository $migrasiTransUploadDokumenPengurusanRepository;
    private Collection $listUploadedFileTahapanFocusPN;
    private int $idSatuanKerjaKPKNL;
    private array $remappingListUploadedFileTahapanFocusPN;
    private array $filterRemappingListUploadFileTahapanFocusPN;

    public function __construct()
    {
        parent::__construct();
        $this->database = new DB();
        $this->storage = new Storage();
        $this->refSatuanKerjaRepository = new RefSatuanKerjaRepository();
        $this->migrasiTransUploadDokumenPengurusanRepository = new MigrasiTransUploadDokumenPengurusanRepository();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:upload-file {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sinkronisasi uploaded file tahapan dimulai!');
        $this->setSatuanKerja()
            ->getListUploadedFileTahapanFocusPN()
            ->mappingListUploadedFileTahapanFocusPN()
            ->filterListUploadedFileTahapanFocusPN()
            ->saveNewData();
        $this->info('Sinkronisasi uploaded file tahapan selesai');
    }

    private function setSatuanKerja()
    {
        $kodeSatuanKerja = $this->argument('kode-satuan-kerja');
        $satuanKerja = $this->refSatuanKerjaRepository->getIdSatuanKerjaByKodeSatuanKerja($kodeSatuanKerja);
        if (!is_null($satuanKerja))
            $this->idSatuanKerjaKPKNL = $satuanKerja->id;
        else
            $this->error('Kode satuan kerja tidak ditemukan');
        return $this;
    }

    private function getListUploadedFileTahapanFocusPN()
    {
        $this->listUploadedFileTahapanFocusPN = $this->migrasiTransUploadDokumenPengurusanRepository->getByIdSatuanKerjaKPKNL($this->idSatuanKerjaKPKNL);
        return $this;
    }

    private function mappingListUploadedFileTahapanFocusPN()
    {
        $this->remappingListUploadedFileTahapanFocusPN = array_map(function($uploadedFile){
            $decodedJson = json_decode($uploadedFile['PATH_TO_FILE']);
            if (is_array($decodedJson) && ($decodedJson !== null || !empty($decodedJson)))
            {
                foreach ($decodedJson as $file)
                {
                    return [
                        'uuid' => Str::uuid()->toString(),
                        'id_trans_tahap_pengurusan' => $uploadedFile['ID_TRANS_TAHAP_PENGURUSAN'],
                        'id_focuspn' => $uploadedFile['ID_FOCUSPN'],
                        'path_to_file' => $this->cloneFile($file, $uploadedFile['CREATED_AT']),
                        'created_by' => $this->getUploadedNIP($file),
                        'created_at' => $uploadedFile['CREATED_AT'],
                        'updated_by' => $this->getUploadedNIP($file),
                        'updated_at' => $uploadedFile['UPDATED_AT']
                    ];
                }
            }
        }, $this->listUploadedFileTahapanFocusPN->toArray());

        return $this;
    }

    private function filterListUploadedFileTahapanFocusPN()
    {
        $this->filterRemappingListUploadFileTahapanFocusPN = array_filter($this->remappingListUploadedFileTahapanFocusPN, fn($value) => !is_null($value));
        return $this;
    }

    private function cloneFile(object $pathToFile, string $createdDate)
    {
        $bucket = 'default';
        $file = '';
        $fileContent = '';
        foreach($pathToFile as $key=>$value)
        {
            // Bucket Name
            if ($key === 'Bucket')
            {
                if ($value === 'sp3n') $bucket = 'sp3n';
                if ($value === 'rhpk') $bucket = 'rhpk';
                if ($value === 'stppn') $bucket = 'stppn';
                if ($value === 'nd_register') $bucket = 'nd-register';
                if ($value === 'hvppn') $bucket = 'hvppn';
                if ($value === 'panggilan') $bucket = 'panggilan';
                if ($value === 'pang_terakhir') $bucket = 'pang-terakhir';
                if ($value === 'peng_panggilan') $bucket = 'peng-panggilan';
                if ($value === 'skpbn') $bucket = 'skpbn';
                if ($value === 'skh') $bucket = 'skh';
                if ($value === 'pkjw') $bucket = 'pkjw';
                if ($value === 's_batal_keringanan') $bucket = 's-batal-keringanan';
                if ($value === 'batj') $bucket = 'batj';
                if ($value === 'pkpb') $bucket = 'pkpb';
                if ($value === 'pbs') $bucket = 'pbs';
                if ($value === 'pbtsuphp') $bucket = 'pbtsuphp';
                if ($value === 'ppb') $bucket = 'ppb';
                if ($value === 'nd_pkpjpn') $bucket = 'nd-pkpjpn';
                if ($value === 'pjpn') $bucket = 'pjpn';
                if ($value === 'nd_terbit_sp') $bucket = 'nd-terbit-sp';
                if ($value === 'pksp') $bucket = 'pksp';
                if ($value === 'surat_paksa') $bucket = 'surat-paksa';
                if ($value === 'bapspaksa') $bucket = 'bapspaksa';
                if ($value === 'lap_pemb_surat_paksa') $bucket = 'lap-pemb-surat-paksa';
                if ($value === 'spb_sp') $bucket = 'spb-sp';
                if ($value === 'perm_blokir') $bucket = 'perm-blokir';
                if ($value === 'pbpp') $bucket = 'pbpp';
                if ($value === 'sps') $bucket = 'sps';
                if ($value === 'psp') $bucket = 'psp';
                if ($value === 'ba_penyitaan') $bucket = 'ba-penyitaan';
                if ($value === 'lap_p_penyitaan') $bucket = 'lap-p-penyitaan';
                if ($value === 'spps') $bucket = 'spps';
                if ($value === 'sppbs') $bucket = 'sppbs';
                if ($value === 'ppnlimit') $bucket = 'ppnlimit';
                if ($value === 'pnlimit') $bucket = 'pnlimit';
                if ($value === 'ppptmldndbn') $bucket = 'ppptmldndbn';
                if ($value === 'ppptml') $bucket = 'ppptml';
                if ($value === 'ppptmle') $bucket = 'ppptmle';
                if ($value === 'ppnpdndbnp') $bucket = 'ppnpdndbnp';
                if ($value === 'nd_penglel1') $bucket = 'nd-penglel1';
                if ($value === 'nd_penglel2') $bucket = 'nd-penglel2';
                if ($value === 'pbpl') $bucket = 'pbpl';
                if ($value === 'ppp') $bucket = 'ppp';
                if ($value === 'pppnebusn') $bucket = 'pppnebusn';
                if ($value === 'rpb') $bucket = 'rpb';
                if ($value === 'pipb') $bucket = 'pipb';
                if ($value === 'sppb') $bucket = 'sppb';
                if ($value === 'ba_pemb_sppb') $bucket = 'ba-pemb-sppb';
                if ($value === 'pikdtpb') $bucket = 'pikdtpb';
                if ($value === 'pikdrbadan') $bucket = 'pikdrbadan';
                if ($value === 'spppb') $bucket = 'spppb';
                if ($value === 'pppopb') $bucket = 'pppopb';
                if ($value === 'ppopb') $bucket = 'ppopb';
                if ($value === 'spppbadan') $bucket = 'spppbadan';
                if ($value === 'pppnsbdd') $bucket = 'pppnsbdd';
                if ($value === 'ppnl') $bucket = 'ppnl';
                if ($value === 'pupppn') $bucket = 'pupppn';
                if ($value === 'pupppne') $bucket = 'pupppne';
                if ($value === 'ppns') $bucket = 'ppns';
                if ($value === 'skppn') $bucket = 'skppn';
                if ($value === 'hasil_pemeriksaan') $bucket = 'hasil-pemeriksaan';
                if ($value === 'psbdt') $bucket = 'psbdt';
                if ($value === 'pntdsm') $bucket = 'pntdsm';
                if ($value === 'spps_setelah') $bucket = 'spps-setelah';
            }

            $newLocation = 'tahapan-pengurusan/'.$bucket.'/'.date('Y/m/d',strtotime($createdDate)).'/';

            // File
            if ($key === 'Key')
            {
                $this->info("Clone ".$value." in progress...");
                $file = explode('/',$value);
                if($this->storage::disk('s3_focuspn')->exists($value))
                {
                    $fileContent = $this->storage::disk('s3_focuspn')->get($value);
                    $newFileLocation = $newLocation.$file[1];
                    $this->storage::disk('s3_modul_pengurusan')->put($newFileLocation, $fileContent);
                    $this->info("Clone ".$value." Done!");
                }
                else
                {
                    return null;
                }
            }
        }

        return $newFileLocation;
    }

    private function getUploadedNIP(object $pathToFile)
    {
        return $pathToFile->Nip;
    }

    private function saveNewData()
    {
        $this->info('Tahapan sinkonrisasi File T_TAHAP ke TRANS_UPLOAD_TAHAP: ');

        $this->database::beginTransaction();
        try
        {
            $chunkData = collect($this->filterRemappingListUploadFileTahapanFocusPN)->chunk(self::TOTAL_DATA_EACH_CHUNK);
            $progressBar = $this->output->createProgressBar(count($chunkData));
            foreach ($chunkData as $chunk)
            {
                $transUploadDokumenPengurusanRepository = new TransUploadDokumenPengurusanRepository();
                $transUploadDokumenPengurusanRepository->insert($chunk->toArray());
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
