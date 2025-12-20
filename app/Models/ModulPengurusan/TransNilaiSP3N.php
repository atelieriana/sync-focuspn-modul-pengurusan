<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransNilaiSP3N extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_NILAI_SP3N';
    public $timestamps = false;
}
