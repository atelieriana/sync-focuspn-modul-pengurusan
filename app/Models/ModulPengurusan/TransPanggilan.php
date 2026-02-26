<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPanggilan extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_PANGGILAN';
    public $timestamps = false;
}
