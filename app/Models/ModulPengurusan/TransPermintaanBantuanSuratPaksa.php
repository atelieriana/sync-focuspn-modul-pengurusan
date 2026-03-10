<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPermintaanBantuanSuratPaksa extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_PERMINTAAN_BANTUAN_SURAT_PAKSA';
    public $timestamps = false;
}
