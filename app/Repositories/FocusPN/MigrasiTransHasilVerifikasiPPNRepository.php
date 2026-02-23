<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransHasilVerifikasiPPN;

class MigrasiTransHasilVerifikasiPPNRepository extends MigrasiTransHasilVerifikasiPPN
{
    public function getByIdSatuanKerjaKPKNL(int $idRefSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idRefSatuanKerjaKPKNL)
            ->get();
    }
}
