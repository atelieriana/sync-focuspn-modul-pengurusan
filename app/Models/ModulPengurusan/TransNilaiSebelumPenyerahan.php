<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransNilaiSebelumPenyerahan extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_nilai_sebelum_penyerahan';
    public $timestamps = false;
}
