<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransSuratKeteranganPengembalian extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_SURAT_KETERANGAN_PENGEMBALIAN';
    public $timestamps = false;
}
