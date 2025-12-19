<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPenjaminHutang extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_PENJAMIN_HUTANG';
    public $timestamps = false;
}
