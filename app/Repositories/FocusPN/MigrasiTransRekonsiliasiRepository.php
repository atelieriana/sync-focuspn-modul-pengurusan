<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransRekonsiliasi;

class MigrasiTransRekonsiliasiRepository extends MigrasiTransRekonsiliasi
{
    public function getIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
