<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransDasarTerjadinyaPiutang extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_dasar_terjadinya_piutang';
    public $timestamps = false;
}
