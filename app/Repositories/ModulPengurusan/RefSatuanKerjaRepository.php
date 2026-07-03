<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\RefSatuanKerja;

class RefSatuanKerjaRepository extends RefSatuanKerja
{
    public function getIdSatuanKerjaByKodeSatuanKerja($kodeSatuanKerja)
    {
        return self::select('id')
            ->where('kode_satuan_kerja_6', $kodeSatuanKerja)
            ->first();
    }
}
