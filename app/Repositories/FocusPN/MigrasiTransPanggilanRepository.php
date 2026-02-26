<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransPanggilan;

class MigrasiTransPanggilanRepository extends MigrasiTransPanggilan
{
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerja)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja)
            ->get();
    }
}
