<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class RefPeraturan extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'ref_peraturan';
}
