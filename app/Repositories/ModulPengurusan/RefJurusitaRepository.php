<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\RefJurusita;

class RefJurusitaRepository extends RefJurusita
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)->forceDelete();
    }
}
