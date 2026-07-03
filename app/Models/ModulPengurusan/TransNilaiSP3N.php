<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransNilaiSP3N extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_nilai_sp3n';
    public $timestamps = false;
}
