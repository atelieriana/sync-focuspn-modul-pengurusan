<?php

namespace App\Console\Commands;

use App\Repositories\ModulPengurusan\RefSatuanKerjaRepository;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\BufferedOutput;

class SyncDataTrans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:transaksi {kode-satuan-kerja : kode satuan kerja 6 digit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Digunakan untuk menjalankan seluruh tahapan migrasi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Migrasi transaksi dimulai!');

        // Sync Ref Pejabat
        $this->call('sync:ref-pejabat',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Ref Klausul PSBDT
        $this->call('sync:ref-klausul-psbdt',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Ref Jurusita
        $this->call('sync:ref-jurusita',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Piutang
        $this->call('sync:trans-piutang',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Debitur
        $this->call('sync:trans-debitur',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Debitur Perseorangan
        $this->call('sync:trans-debitur-perseorangan',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Debitur Badan Hukum
        $this->call('sync:trans-debitur-badan-hukum',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Pengurus Badan Hukum
        $this->call('sync:trans-pengurus-badan-hukum',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Penjamin Hutang Lainnya
        $this->call('sync:trans-penjamin-hutang',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);
        
        // Sync Trans Tahap Pengurusan
        $this->call('sync:trans-tahap-pengurusan',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Tahap Pengurusan
        $this->call('sync:trans-nilai-penyerahan-piutang',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Nilai SP3n
        $this->call('sync:trans-nilai-sp3n',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Angsuran
        $this->call('sync:trans-angsuran',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Koreksi
        $this->call('sync:trans-koreksi',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Keringanan Usulan
        $this->call('sync:trans-keringanan-usulan',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Keringanan Setuju
        $this->call('sync:trans-keringanan-setuju',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Berita Acara Surat Paksa
        $this->call('sync:trans-berita-acara-surat-paksa',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Berita Acara Tanya Jawab
        $this->call('sync:trans-berita-acara-tanya-jawab',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Berita Acara Penyitaan
        $this->call('sync:trans-berita-acara-penyitaan',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Dasar Terjadinya Piutang
        $this->call('sync:trans-dasar-terjadinya-piutang',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Barang Jaminan
        $this->call('sync:trans-barang-jaminan',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja'),
        ]);

        // Sync Trans Laporan Pen
        $this->call('sync:trans-laporan-pemberitahuan-surat-paksa', [
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans Nilai Pernyataan Bersama PJPN
        $this->call('sync:trans-nilai-pernyataan-bersama-pjpn',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans Nilai Sebelum Penyerahan
        $this->call('sync:trans-nilai-sebelum-penyerahan',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans Panggilan
        $this->call('sync:trans-panggilan',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans Uraian Pernyataan Bersama PJPN
        $this->call('sync:trans-uraian-pernyataan-bersama-pjpn',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans Permintaan Pemblokiran
        $this->call('sync:trans-permintaan-pemblokiran',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans PPDTO
        $this->call('sync:trans-ppdto',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans PPDTO NOminal
        $this->call('sync:trans-ppdto-nominal',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans PPDTO
        $this->call('sync:trans-ppnto',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);
        
        // Sync Trans PPNTO NOminal
        $this->call('sync:trans-ppnto-nominal',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans Rekonsiliasi
        $this->call('sync:trans-rekonsiliasi',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans Surat Keterangan Pengembalian
        $this->call('sync:trans-surat-keterangan-pengembalian',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans Permintaan Bantuan Surat Paksa
        $this->call('sync:trans-permintaan-bantuan-surat-paksa',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans Dihapus Mutlak
        $this->call('sync:trans-dihapus-mutlak',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        // Sync Trans Surat Paksa
        $this->call('sync:trans-surat-paksa',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

                // Sync Trans Surat Paksa
        $this->call('sync:trans-tembusan',[
            'kode-satuan-kerja' => $this->argument('kode-satuan-kerja')
        ]);

        $this->info('Migrasi transaksi selesai!');
    }
}
