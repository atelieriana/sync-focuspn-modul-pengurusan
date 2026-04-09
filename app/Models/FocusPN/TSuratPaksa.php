<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TSuratPaksa extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_SURAT_PAKSA';
    public $timestamps = false;
}
