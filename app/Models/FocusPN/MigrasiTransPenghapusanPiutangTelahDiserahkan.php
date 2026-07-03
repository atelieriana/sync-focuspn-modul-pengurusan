<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransPenghapusanPiutangTelahDiserahkan extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANS_PENGHAPUSAN_PIUTANG_TELAH_DISERAHKAN';
}
