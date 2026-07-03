<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\InterkoneksiOJK;

class InterkoneksiOJKRepository extends InterkoneksiOJK
{
    public function getByIdRefSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
            ->get();
    }

    public function deleteByIdRefSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
            ->delete();
    }
}
