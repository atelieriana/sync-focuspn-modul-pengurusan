<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPanggilan extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_panggilan';
    public $timestamps = false;
}
