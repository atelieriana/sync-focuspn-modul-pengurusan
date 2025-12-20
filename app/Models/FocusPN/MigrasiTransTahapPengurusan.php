<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransTahapPengurusan extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANS_TAHAP_PENGURUSAN';
}
