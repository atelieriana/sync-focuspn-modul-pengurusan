<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TSPTDMHapus extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_SPTDM_HAPUS';
    public $timestamps = false;
}
