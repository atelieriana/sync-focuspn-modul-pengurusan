<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class RefKlausulPSBDT extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'REF_KLAUSUL_PSBDT';
    public $timestamps = false;
}
