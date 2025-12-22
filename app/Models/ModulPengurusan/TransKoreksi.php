<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransKoreksi extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_KOREKSI';
    public $timestamps = false;
}
