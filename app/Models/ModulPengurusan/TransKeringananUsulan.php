<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransKeringananUsulan extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_keringanan_usulan';
    public $timestamps = false;
}
