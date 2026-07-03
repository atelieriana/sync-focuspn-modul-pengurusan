<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransNilaiPernyataanBersamaPJPN extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_nilai_pernyataan_bersama_pjpn';
    public $timestamps = false;
}
