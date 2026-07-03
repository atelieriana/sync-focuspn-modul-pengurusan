<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransSuratKeteranganPengembalian extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_surat_keterangan_pengembalian';
    public $timestamps = false;
}
