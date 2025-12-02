<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\RefSatuanKerja;

class RefSatuanKerjaRepository extends RefSatuanKerja
{
    public function getIdSatuanKerjaByKodeSatuanKerja($kodeSatuanKerja)
    {
        return self::select('ID')
            ->where('KODE_SATUAN_KERJA_6', $kodeSatuanKerja)
            ->first();
    }
}
