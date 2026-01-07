<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransKeringananSetuju extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_KERINGANAN_SETUJU';
    public $timestamps = false;
}