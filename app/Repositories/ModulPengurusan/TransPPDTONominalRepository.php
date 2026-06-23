<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPPDTONominal;

class TransPPDTONominalRepository extends TransPPDTONominal
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_ppdto', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('id')
                ->from('trans_ppdto')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->forceDelete();
    }

    public function getyIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_ppdto', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('id')
                ->from('trans_ppdto')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
