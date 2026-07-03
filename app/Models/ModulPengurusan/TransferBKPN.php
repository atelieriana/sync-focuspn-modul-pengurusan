<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransferBKPN extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'transfer_bkpn';
    public $timestamps = false;
}
