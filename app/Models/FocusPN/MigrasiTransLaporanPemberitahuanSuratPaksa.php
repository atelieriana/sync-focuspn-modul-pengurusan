<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransLaporanPemberitahuanSuratPaksa extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANS_LAPORAN_PEMBERITAHUAN_SURAT_PAKSA';
}
