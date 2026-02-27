<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPermintaanPemblokiran extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_PERMINTAAN_PEMBLOKIRAN';
    public $timestamps = false;
}
