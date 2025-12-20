<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransTahapPengurusan extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_TAHAP_PENGURUSAN';
    public $timestamps = false;
}
