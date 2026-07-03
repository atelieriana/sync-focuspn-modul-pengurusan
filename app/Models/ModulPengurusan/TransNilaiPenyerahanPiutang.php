<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransNilaiPenyerahanPiutang extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_nilai_penyerahan_piutang';
    public $timestamps = false;
}
