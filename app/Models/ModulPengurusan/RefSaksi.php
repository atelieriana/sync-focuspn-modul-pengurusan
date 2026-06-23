<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class RefSaksi extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'ref_saksi';
}
