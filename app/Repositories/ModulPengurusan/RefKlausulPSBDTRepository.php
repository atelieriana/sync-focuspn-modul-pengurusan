<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\RefKlausulPSBDT;

class RefKlausulPSBDTRepository extends RefKlausulPSBDT
{
    public function getByIdSatuanKerja(int $idSatuanKerja)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja)
            ->get();
    }
    
    /**
     * Digunakan untuk melakukan penghapusan klausul PSBDT berdasarkan id satuan kerja
     * @param $idSatuanKerja
     * @return mixed
     */
    public function deleteByIdSatuanKerja($idSatuanKerja)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja)->forceDelete();
    }
}
