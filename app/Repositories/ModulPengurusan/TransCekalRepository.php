<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransCekal;

class TransCekalRepository extends TransCekal
{
    public function deleteByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_DEBITUR', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('ID')
                ->from('TRANS_DEBITUR')
                ->whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerjaKPKNL){
                    $subQuery->select('ID')
                    ->from('TRANS_PIUTANG')
                    ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
            });
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_DEBITUR', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('ID')
                ->from('TRANS_DEBITUR')
                ->whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerjaKPKNL){
                    $subQuery->select('ID')
                    ->from('TRANS_PIUTANG')
                    ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
            });
        })
            ->get();
    }
}
