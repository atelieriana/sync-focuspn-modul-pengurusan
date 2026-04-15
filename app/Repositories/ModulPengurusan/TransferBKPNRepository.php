<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransferBKPN;

class TransferBKPNRepository extends TransferBKPN
{
    public function deleteByIdSatuanKerjaKPKNLAsal(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_SATUAN_KERJA_KPKNL_ASAL', $idSatuanKerjaKPKNL)
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNLAsal(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_SATUAN_KERJA_KPKNL_ASAL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
