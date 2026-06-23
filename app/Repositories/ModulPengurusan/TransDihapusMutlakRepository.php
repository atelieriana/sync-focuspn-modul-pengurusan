<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransDihapusMutlak;

class TransDihapusMutlakRepository extends TransDihapusMutlak
{
    public function deleteByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
            ->forceDelete();
    }

    public function getByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
            ->get();
    }
}
