<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPenghapusanPiutangTelahDiserahkan extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_penghapusan_piutang_telah_diserahkan';
    public $timestamps = false;
}
