<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransAngsuran extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_ANGSURAN';
    public $timestamps = false;
}
