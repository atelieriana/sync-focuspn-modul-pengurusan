<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransPenjaminHutang extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANS_PENJAMIN_HUTANG';
}
