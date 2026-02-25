<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransNilaiPernyataanBersamaPJPN extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_NILAI_PERNYATAAN_BERSAMA_PJPN';
    public $timestamps = false;
}
