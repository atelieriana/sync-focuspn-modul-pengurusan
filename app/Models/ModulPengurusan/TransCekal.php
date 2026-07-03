<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransCekal extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_cekal';
    public $timestamps = false;
}
