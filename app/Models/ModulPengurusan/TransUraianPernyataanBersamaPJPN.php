<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransUraianPernyataanBersamaPJPN extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_URAIAN_PERNYATAAN_BERSAMA_PJPN';
    public $timestamps = false;
}
