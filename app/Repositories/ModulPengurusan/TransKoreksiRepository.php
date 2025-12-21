<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransKoreksi;

class TransKoreksiRepository extends TransKoreksi
{
    public function deleteByIdSatuanKerja(int $idSatuanKerja)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerja) {
            return $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerja(int $idSatuanKerja)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerja) {
            return $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja);
        })
            ->get();
    }
}
