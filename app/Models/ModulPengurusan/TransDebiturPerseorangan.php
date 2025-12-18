<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransDebiturPerseorangan extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_DEBITUR_PERSEORANGAN';
    public $timestamps = false;
}
