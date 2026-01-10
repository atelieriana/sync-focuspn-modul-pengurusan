<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class RefKurs extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'REF_KURS';
    public $timestamps = false;
}
