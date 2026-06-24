<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransLaporanPemberitahuanSuratPaksa extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_laporan_pemberitahuan_surat_paksa';
    public $timestamps = false;
}
