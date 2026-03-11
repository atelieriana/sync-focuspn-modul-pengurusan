<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPenolakanPiutangNegara extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_PENOLAKAN_PIUTANG_NEGARA';
    public $timestamps = false;
}
