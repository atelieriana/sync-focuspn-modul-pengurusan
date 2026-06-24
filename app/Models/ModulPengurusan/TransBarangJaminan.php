<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransBarangJaminan extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_barang_jaminan';
    public $timestamps = false;
}
