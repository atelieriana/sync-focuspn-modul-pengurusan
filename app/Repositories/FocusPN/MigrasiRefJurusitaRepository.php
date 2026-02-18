<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiRefJurusita;

class MigrasiRefJurusitaRepository extends MigrasiRefJurusita
{
    public function getByIdSatuanKerjaKPKNL(int $idRefSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idRefSatuanKerjaKPKNL)->get();
    }
}