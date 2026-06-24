<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransBeritaAcaraSuratPaksa extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_berita_acara_surat_paksa';
    public $timestamps = false;
}
