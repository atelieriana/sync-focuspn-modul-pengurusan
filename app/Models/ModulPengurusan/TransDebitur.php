<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransDebitur extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_DEBITUR';
    public $timestamps = false;
}
