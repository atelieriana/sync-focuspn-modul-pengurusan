<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class RefKurs extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'ref_kurs';
    public $timestamps = false;
}
