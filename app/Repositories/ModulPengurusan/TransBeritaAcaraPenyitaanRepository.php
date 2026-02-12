<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransBeritaAcaraPenyitaan;

class TransBeritaAcaraPenyitaanRepository extends TransBeritaAcaraPenyitaan
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerja)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerja){
            return $subQuery->select('ID_TRANS_PIUTANG')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerja)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerja){
            return $subQuery->select('ID_TRANS_PIUTANG')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja);
        })
            ->get();
    }
}
