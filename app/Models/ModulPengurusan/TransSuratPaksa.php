<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransSuratPaksa extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_surat_paksa';
    public $timestamps = false;
}
