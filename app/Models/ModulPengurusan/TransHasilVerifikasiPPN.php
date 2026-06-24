<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransHasilVerifikasiPPN extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_hasil_verifikasi_ppn';
    public $timestamps = false;
}
