<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransRekonsiliasi extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_rekonsiliasi';
    public $timestamps = false;
}
