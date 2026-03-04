<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPPNTO extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_PPNTO';
    public $timestamps = false;
}
