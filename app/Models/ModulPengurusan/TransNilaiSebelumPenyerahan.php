<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransNilaiSebelumPenyerahan extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_NILAI_SEBELUM_PENYERAHAN';
    public $timestamps = false;
}
