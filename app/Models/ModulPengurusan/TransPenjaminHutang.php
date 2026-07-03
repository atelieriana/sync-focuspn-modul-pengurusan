<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPenjaminHutang extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_penjamin_hutang';
    public $timestamps = false;
}
