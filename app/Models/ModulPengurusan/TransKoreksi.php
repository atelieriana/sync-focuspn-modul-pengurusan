<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransKoreksi extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_koreksi';
    public $timestamps = false;
}
