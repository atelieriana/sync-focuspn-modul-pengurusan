<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransPPDTONominal;

class MigrasiTransPPDTONominalRepository extends MigrasiTransPPDTONominal
{
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
