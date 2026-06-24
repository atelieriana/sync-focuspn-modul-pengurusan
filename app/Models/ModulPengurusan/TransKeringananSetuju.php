<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransKeringananSetuju extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_keringanan_setuju';
    public $timestamps = false;
}
