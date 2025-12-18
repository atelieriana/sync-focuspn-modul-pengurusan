<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransDebiturBadanHukum extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_DEBITUR_BADAN_HUKUM';
    public $timestamps = false;
}
