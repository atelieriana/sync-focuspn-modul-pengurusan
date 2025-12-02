<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class RefSatuanKerja extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'REF_SATUAN_KERJA';
}
