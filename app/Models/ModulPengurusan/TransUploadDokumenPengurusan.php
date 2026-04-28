<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransUploadDokumenPengurusan extends Model
{
    protected $connection = 'oracle_modul_pengurusan';
    protected $table = 'TRANS_UPLOAD_DOKUMEN_PENGURUSAN';
    public $timestamps = false;
}
