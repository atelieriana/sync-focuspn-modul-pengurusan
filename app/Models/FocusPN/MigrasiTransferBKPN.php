<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransferBKPN extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANSFER_BKPN';
    public $timestamps = false;
}
