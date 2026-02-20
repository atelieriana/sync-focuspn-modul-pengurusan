<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransDasarTerjadinyaPiutang;

class TransDasarTerjadinyaPiutangRepository extends TransDasarTerjadinyaPiutang
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
