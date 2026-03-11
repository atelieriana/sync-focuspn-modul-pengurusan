<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransDihapusMutlak extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_DIHAPUS_MUTLAK';
    public $timestamps = false;
}
