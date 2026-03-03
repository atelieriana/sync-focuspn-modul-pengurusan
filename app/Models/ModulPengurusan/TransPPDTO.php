<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPPDTO extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_PPDTO';
    public $timestamps = false;
}
