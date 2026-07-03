<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransBeritaAcaraTanyaJawab extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_berita_acara_tanya_jawab';
    public $timestamps = false;
}
