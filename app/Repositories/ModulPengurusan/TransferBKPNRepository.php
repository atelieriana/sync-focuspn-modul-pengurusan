<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransferBKPN;

class TransferBKPNRepository extends TransferBKPN
{
    public function deleteByIdSatuanKerjaKPKNLAsal(int $idSatuanKerjaKPKNL)
    {
        return self::where('id_satuan_kerja_kpknl_asal', $idSatuanKerjaKPKNL)
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNLAsal(int $idSatuanKerjaKPKNL)
    {
        return self::where('id_satuan_kerja_kpknl_asal', $idSatuanKerjaKPKNL)
            ->get();
    }
}
