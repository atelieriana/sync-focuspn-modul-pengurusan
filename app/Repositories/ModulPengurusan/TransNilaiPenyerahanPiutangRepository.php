<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransNilaiPenyerahanPiutang;

class TransNilaiPenyerahanPiutangRepository extends TransNilaiPenyerahanPiutang
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_piutang', function ($subQuery) use ($idSatuanKerjaKPKNL) {
            return $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_piutang', function ($subQuery) use ($idSatuanKerjaKPKNL) {
            return $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
