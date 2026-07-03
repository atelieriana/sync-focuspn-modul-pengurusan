<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransDebiturBadanHukum extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_debitur_badan_hukum';
    public $timestamps = false;
}
