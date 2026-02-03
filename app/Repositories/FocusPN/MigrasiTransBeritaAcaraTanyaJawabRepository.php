<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransBeritaAcaraTanyaJawab;

class MigrasiTransBeritaAcaraTanyaJawabRepository extends MigrasiTransBeritaAcaraTanyaJawab
{
    public function getByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
