<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransferBKPNDetail extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANSFER_BKPN_DETAIL';
    public $timestamps = false;
}
