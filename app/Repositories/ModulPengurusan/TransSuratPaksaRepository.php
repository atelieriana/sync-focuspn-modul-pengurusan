<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransSuratPaksa;

class TransSuratPaksaRepository extends TransSuratPaksa
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
                ->get();
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
                ->get();
        })
            ->get();
    }
}
