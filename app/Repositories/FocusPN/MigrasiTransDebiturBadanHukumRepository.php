<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransDebiturBadanHukum;

class MigrasiTransDebiturBadanHukumRepository extends MigrasiTransDebiturBadanHukum
{
    /**
     * Digunakan untuk mengambil data debitur perseorangan berdasarkan id ref satuan kerja KPKNL
     * @param int $idSatuanKerjaKPKNL
     * @return mixed
     */
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
