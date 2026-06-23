<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPPNTONominal;

class TransPPNTONominalRepository extends TransPPNTONominal
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_ppnto', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('id')
                ->from('trans_ppnto')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->forceDelete();
    }

    public function getyIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_ppnto', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('id')
                ->from('trans_ppnto')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
