<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPernyataanPiutangTelahOptimalNominal extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_pernyataan_piutang_telah_optimal_nominal';
    public $timestamps = false;
}
