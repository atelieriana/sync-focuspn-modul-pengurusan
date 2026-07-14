<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransNilaiBarangJaminan extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANS_NILAI_BARANG_JAMINAN';
    public $timestamps = false;
}
