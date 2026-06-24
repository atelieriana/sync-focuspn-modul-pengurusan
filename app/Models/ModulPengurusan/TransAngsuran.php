<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransAngsuran extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_angsuran';
    public $timestamps = false;
}
