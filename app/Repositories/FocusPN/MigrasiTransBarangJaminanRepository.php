<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransBarangJaminan;

class MigrasiTransBarangJaminanRepository extends MigrasiTransBarangJaminan
{
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjakPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjakPKNL)
            ->get();
    }
}
