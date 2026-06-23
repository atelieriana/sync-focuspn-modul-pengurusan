<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPPNTO;

class TransPPNTORepository extends TransPPNTO
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
            ->get();
    }
}
