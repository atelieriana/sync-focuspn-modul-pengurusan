<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransUraianPernyataanBersamaPJPN extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_uraian_pernyataan_bersama_pjpn';
    public $timestamps = false;
}
