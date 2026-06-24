<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransBelumDapatDitagih extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_belum_dapat_ditagih';
    public $timestamps = false;
}
