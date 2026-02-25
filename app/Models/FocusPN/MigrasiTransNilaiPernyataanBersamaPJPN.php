<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransNilaiPernyataanBersamaPJPN extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANS_NILAI_PERNYATAAN_BERSAMA_PJPN';
    public $timestamps = false;
}
