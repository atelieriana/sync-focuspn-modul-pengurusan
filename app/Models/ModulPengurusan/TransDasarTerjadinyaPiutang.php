<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransDasarTerjadinyaPiutang extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_DASAR_TERJADINYA_PIUTANG';
    public $timestamps = false;
}
