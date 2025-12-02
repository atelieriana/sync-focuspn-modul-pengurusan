<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\RefPejabat;
use Illuminate\Database\Eloquent\Model;

class RefPejabatRepository extends RefPejabat
{
    /**
     * Digunakan untuk melakukan force delete berdasarkan id satuan kerja
     * @param $idSatuanKerja
     * @return void
     */
    public function deleteByIdSatuanKerja($idSatuanKerja)
    {
        self::where('ID_REF_SATUAN_KERJA', $idSatuanKerja)->forceDelete();
    }
}
