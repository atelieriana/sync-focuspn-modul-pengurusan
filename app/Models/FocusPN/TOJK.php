<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TOJK extends Model
{
    protected $connection = 'oracle_dashboard';
    protected $table = 'T_OJK';
    public $timestamps = false;
}
