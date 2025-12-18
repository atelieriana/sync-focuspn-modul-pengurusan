<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransDebitur;

class MigrasiTransDebiturRepository extends MigrasiTransDebitur
{
    /**
     * Digunakan untuk mendapatkan data trans debitur berdasarkan id satuan kerja kpknl
     * @param int $idSatuanKerja
     * @return mixed
     */
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerja)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja)
            ->get();
    }
}
