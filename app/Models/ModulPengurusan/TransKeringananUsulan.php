<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransKeringananUsulan extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_KERINGANAN_USULAN';
    public $timestamps = false;
}
