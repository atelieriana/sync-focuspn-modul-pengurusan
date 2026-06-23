<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransSuratPaksa;

class TransSuratPaksaRepository extends TransSuratPaksa
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_tahap_pengurusan', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('id')
                ->from('trans_tahap_pengurusan')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
                ->get();
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_tahap_pengurusan', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('id')
                ->from('trans_tahap_pengurusan')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
                ->get();
        })
            ->get();
    }
}
