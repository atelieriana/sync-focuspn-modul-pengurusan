<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class RefPejabat extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'ref_pejabat';
    public $timestamps = false;
}
