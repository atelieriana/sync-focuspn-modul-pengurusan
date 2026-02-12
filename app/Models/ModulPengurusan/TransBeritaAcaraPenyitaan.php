<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransBeritaAcaraPenyitaan extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_BERITA_ACARA_PENYITAAN';
    public $timestamps = false;
}
