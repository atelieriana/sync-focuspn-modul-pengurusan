<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransCekal extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_CEKAL';
    public $timestamps = false;
}
