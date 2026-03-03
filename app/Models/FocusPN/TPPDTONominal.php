<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TPPDTONominal extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_PPDTO_NOMINAL';
    public $timestamps = false;
}
