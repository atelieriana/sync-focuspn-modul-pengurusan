<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPiutang;

class TransPiutangRepository extends TransPiutang
{
    /**
     * @param int $idSatuanKerjaKPKNL
     * @return mixed
     */
    public function deleteByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->forceDelete();
    }
}
