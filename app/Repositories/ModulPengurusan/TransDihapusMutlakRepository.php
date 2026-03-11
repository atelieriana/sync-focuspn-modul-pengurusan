<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransDihapusMutlak;

class TransDihapusMutlakRepository extends TransDihapusMutlak
{
    public function deleteByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->forceDelete();
    }

    public function getByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
