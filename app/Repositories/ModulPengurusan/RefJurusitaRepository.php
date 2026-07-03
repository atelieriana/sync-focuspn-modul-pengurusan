<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\RefJurusita;

class RefJurusitaRepository extends RefJurusita
{
    public function getByIdRefSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)->get();
    }

    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)->forceDelete();
    }
}
