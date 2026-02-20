<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransDasarTerjadinyaPiutang extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANS_DASAR_TERJADINYA_PIUTANG';
    public $timestamps = false;
}
