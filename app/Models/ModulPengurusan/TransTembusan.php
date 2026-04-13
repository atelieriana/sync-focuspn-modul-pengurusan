<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransTembusan extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_TEMBUSAN';
    public $timestamps = false;
}
