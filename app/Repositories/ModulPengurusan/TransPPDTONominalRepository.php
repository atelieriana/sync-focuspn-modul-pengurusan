<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPPDTONominal;

class TransPPDTONominalRepository extends TransPPDTONominal
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PPDTO', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('ID')
                ->from('TRANS_PPDTO')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
        })
            ->forceDelete();
    }

    public function getyIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PPDTO', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('ID')
                ->from('TRANS_PPDTO')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
