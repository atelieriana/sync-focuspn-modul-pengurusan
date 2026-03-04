<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPPNTONominal extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_PPNTO_NOMINAL';
}
