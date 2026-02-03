<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransBeritaAcaraTanyaJawab extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_BERITA_ACARA_TANYA_JAWAB';
    public $timestamps = false;
}
