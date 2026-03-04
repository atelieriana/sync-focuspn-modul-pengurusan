<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPPNTONominal;

class TransPPNTONominalRepository extends TransPPNTONominal
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PPNTO', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('ID')
                ->from('TRANS_PPNTO')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
        })
            ->forceDelete();
    }

    public function getyIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PPNTO', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('ID')
                ->from('TRANS_PPNTO')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
