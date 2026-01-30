<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransBeritaAcaraSuratPaksa extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_BERITA_ACARA_SURAT_PAKSA';
    public $timestamps = false;
}
