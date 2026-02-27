<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransPermintaanPemblokiran;

class MigrasiTransPermintaanPemblokiranRepository extends MigrasiTransPermintaanPemblokiran
{
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerja)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja)
            ->get();
    }
}
