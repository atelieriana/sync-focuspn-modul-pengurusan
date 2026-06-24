<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPPDTONominal extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_ppdto_nominal';
    public $timestamps = false;
}
