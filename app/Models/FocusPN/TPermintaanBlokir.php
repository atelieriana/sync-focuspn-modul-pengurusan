<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TPermintaanBlokir extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_PERMINTAAN_BLOKIR';
    public $timestamps = false;
}
