<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransLaporanPemberitahuanSuratPaksa extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_LAPORAN_PEMBERITAHUAN_SURAT_PAKSA';
    public $timestamps = false;
}
