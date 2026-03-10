<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransPermintaanBantuanSuratPaksa extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANS_PERMINTAAN_BANTUAN_SURAT_PAKSA';
}
