<?php

namespace App\Models\ModulPengurusan;

use Illuminate\Database\Eloquent\Model;

class TransUploadDokumenPengurusan extends Model
{
    protected $connection = 'pgsql_pengurusan';
    protected $table = 'trans_upload_dokumen_pengurusan';
    public $timestamps = false;
}
