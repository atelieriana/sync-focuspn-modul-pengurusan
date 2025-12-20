<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransTahapPengurusan;

class TransTahapPengurusanRepository extends TransTahapPengurusan
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function ($subQuery) use ($idSatuanKerjaKPKNL) {
            return $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function ($subQuery) use ($idSatuanKerjaKPKNL) {
            return $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
