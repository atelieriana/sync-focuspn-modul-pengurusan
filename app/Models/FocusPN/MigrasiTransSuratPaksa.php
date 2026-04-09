<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransSuratPaksa extends Model
{
    protected $connection = 'oracle_focuspn'; 
    protected $table = 'MIGRASI_TRANS_SURAT_PAKSA';
}
