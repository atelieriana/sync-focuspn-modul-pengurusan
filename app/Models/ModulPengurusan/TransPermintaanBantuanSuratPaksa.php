<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransPermintaanBantuanSuratPaksa extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_permintaan_bantuan_surat_paksa';
    public $timestamps = false;
}
