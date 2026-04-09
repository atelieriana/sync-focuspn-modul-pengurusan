<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransSuratPaksa extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_SURAT_PAKSA';
    public $timestamps = false;
}
