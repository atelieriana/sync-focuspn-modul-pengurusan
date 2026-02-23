<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransHasilVerifikasiPPN extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_HASIL_VERIFIKASI_PPN';
    public $timestamps = false;
}
