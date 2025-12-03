<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiRefPeraturan extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_REF_PERATURAN';
}
