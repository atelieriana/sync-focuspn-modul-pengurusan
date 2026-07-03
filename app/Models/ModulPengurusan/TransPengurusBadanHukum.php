<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPengurusBadanHukum extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_pengurus_badan_hukum';
    public $timestamps = false;
}
