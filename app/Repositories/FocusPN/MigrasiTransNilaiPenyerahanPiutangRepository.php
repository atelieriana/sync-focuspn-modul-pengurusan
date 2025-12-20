<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransNilaiPenyerahanPiutang;

class MigrasiTransNilaiPenyerahanPiutangRepository extends MigrasiTransNilaiPenyerahanPiutang
{
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
