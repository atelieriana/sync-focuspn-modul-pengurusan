<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class InterkoneksiOJK extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'interkoneksi_ojk';
}
