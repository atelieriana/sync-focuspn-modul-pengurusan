<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransDebitur extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_debitur';
    public $timestamps = false;
}
