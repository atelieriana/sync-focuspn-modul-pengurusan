<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransTembusan extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_tembusan';
    public $timestamps = false;
}
