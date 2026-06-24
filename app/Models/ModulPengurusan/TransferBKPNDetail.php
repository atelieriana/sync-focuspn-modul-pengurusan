<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransferBKPNDetail extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'transfer_bkpn_detail';
    public $timestamp = false;
}
