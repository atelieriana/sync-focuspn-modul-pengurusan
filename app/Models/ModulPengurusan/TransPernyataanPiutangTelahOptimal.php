<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPernyataanPiutangTelahOptimal extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_pernyataan_piutang_telah_optimal';
    public $timestamps = false;
}
