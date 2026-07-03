<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPiutang extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_piutang';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
