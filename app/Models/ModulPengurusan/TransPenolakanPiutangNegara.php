<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPenolakanPiutangNegara extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_penolakan_piutang_negara';
    public $timestamps = false;
}
