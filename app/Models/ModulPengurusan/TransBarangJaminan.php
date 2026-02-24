<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransBarangJaminan extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_BARANG_JAMINAN';
    public $timestamps = false;
}
