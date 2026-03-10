<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransRekonsiliasi extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_REKONSILIASI';
    public $timestamps = false;
}
