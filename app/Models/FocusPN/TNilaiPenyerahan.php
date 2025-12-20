<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TNilaiPenyerahan extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_NILAIPENYERAHAN';
    public $timestamps = false;
}
