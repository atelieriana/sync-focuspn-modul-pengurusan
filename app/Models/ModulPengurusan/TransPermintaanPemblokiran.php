<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPermintaanPemblokiran extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_permintaan_pemblokiran';
    public $timestamps = false;
}
