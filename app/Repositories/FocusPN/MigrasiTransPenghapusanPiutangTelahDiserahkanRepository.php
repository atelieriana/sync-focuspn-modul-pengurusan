<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransPenghapusanPiutangTelahDiserahkan;

class MigrasiTransPenghapusanPiutangTelahDiserahkanRepository extends MigrasiTransPenghapusanPiutangTelahDiserahkan
{
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
