<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransDihapusMutlak extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_dihapus_mutlak';
    public $timestamps = false;
}
