<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class TTransferArsip extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'T_TRANSFER_ARSIP';
    public $timestamps = false;
}
