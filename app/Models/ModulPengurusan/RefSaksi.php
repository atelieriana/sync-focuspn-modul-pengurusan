<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class RefSaksi extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'REF_SAKSI';
}
