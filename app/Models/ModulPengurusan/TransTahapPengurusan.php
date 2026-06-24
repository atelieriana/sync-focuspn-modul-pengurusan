<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransTahapPengurusan extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_tahap_pengurusan';
    public $timestamps = false;
}
