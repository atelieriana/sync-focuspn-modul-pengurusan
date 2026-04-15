<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransferBKPNDetail;

class MigrasiTransferBKPNDetailRepository extends MigrasiTransferBKPNDetail
{
    public function getByIdSatuanKerjaKPKNLAsal(int $idSatuanKerjaKPKNLAsal)
    {
        return self::where('ID_SATUAN_KERJA_KPKNL_ASAL', $idSatuanKerjaKPKNLAsal)
            ->get();
    }
}
