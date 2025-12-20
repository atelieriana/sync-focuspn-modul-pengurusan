<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransNilaiPenyerahanPiutang extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_NILAI_PENYERAHAN_PIUTANG';
    public $timestamps = false;
}
