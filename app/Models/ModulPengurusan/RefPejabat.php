<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class RefPejabat extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'REF_PEJABAT';
    public $timestamps = false;
}
