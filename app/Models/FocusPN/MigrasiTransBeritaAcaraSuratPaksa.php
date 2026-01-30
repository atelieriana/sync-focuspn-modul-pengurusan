<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransBeritaAcaraSuratPaksa extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANS_BERITA_ACARA_SURAT_PAKSA';
    public $timestamps = false;
}
