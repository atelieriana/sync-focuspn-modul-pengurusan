<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPPDTO extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_ppdto';
    public $timestamps = false;
}
