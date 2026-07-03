<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class RefKlausulPSBDT extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'ref_klausul_psbdt';
    public $timestamps = false;
}
