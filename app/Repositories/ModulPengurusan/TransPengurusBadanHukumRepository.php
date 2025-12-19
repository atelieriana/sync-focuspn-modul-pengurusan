<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPengurusBadanHukum;

class TransPengurusBadanHukumRepository extends TransPengurusBadanHukum
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerja)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function ($subQuery) use ($idSatuanKerja) {
            $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerja)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function ($subQuery) use ($idSatuanKerja) {
            $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja);
        })
            ->get();
    }
}
