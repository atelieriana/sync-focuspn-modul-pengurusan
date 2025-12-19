<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPengurusBadanHukum extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_PENGURUS_BADAN_HUKUM';
    public $timestamps = false;
}
