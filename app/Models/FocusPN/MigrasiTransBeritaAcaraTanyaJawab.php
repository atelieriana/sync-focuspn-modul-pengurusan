<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransBeritaAcaraTanyaJawab extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANS_BERITA_ACARA_TANYA_JAWAB';
    public $timestamps = false;
}
