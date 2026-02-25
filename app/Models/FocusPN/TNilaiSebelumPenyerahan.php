<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TNilaiSebelumPenyerahan extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_NILAISEBELUMPENYERAHAN';
    public $timestamps = false;
}
