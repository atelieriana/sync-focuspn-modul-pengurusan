<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransPiutang;

class MigrasiTransPiutangRepository extends MigrasiTransPiutang
{
    /**
     * Digunakan untuk mendapatkan data trans piutang berdasarkan id satuan kerja
     * @param int $idSatuanKerja
     * @return mixed
     */
    public function getByIdSatuanKerja(int $idSatuanKerja)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja)
            ->get();
    }
}
