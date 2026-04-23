<?php

namespace App\Models\FocusPN;

use Illuminate\Database\Eloquent\Model;

class MigrasiTransUploadDokumenPengurusan extends Model
{
    protected $connection = 'oracle_focuspn';
    protected $table = 'MIGRASI_TRANS_UPLOAD_DOKUMEN_PENGURUSAN';
    public $timestamps = false;
}
