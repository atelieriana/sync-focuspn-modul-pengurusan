<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransDebiturPerseorangan extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_debitur_perseorangan';
    public $timestamps = false;
}
