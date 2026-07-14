<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransNilaiAppraisalBarangJaminan extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_nilai_appraisal_barang_jaminan';
    public $timestamps = false;
}
