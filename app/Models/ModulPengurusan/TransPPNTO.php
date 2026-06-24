<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPPNTO extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_ppnto';
    public $timestamps = false;
}
