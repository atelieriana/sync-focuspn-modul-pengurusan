<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransBelumDapatDitagih extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_BELUM_DAPAT_DITAGIH';
    public $timestamps = false;
}
