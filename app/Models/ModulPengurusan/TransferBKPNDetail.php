<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransferBKPNDetail extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANSFER_BKPN_DETAIL';
    public $timestamp = false;
}
