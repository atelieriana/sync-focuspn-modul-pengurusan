<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransTembusan;

class MigrasiTransTembusanRepository extends MigrasiTransTembusan
{
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
