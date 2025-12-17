<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPiutang extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_PIUTANG';
    protected $primaryKey = 'ID';
    public $timestamps = false;
}
