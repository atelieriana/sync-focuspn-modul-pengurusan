<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPPDTONominal extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_PPDTO_NOMINAL';
    public $timestamps = false;
}
