<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransPengurusBadanHukum;

class MigrasiTransPengurusBadanHukumRepository extends MigrasiTransPengurusBadanHukum
{
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
