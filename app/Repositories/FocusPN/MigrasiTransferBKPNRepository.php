<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransferBKPN;

class MigrasiTransferBKPNRepository extends MigrasiTransferBKPN
{
    public function getByIdSatuanKerjaKPKNLAsal(int $idSatuanKerjakPKNL)
    {
        return self::where('ID_SATUAN_KERJA_KPKNL_ASAL', $idSatuanKerjakPKNL)
            ->get();
    }
}
