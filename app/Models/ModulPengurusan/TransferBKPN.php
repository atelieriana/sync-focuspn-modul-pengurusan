<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransferBKPN extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANSFER_BKPN';
    public $timestamps = false;
}
