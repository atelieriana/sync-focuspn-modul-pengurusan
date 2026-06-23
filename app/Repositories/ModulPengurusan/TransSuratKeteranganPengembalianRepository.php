<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransSuratKeteranganPengembalian;

class TransSuratKeteranganPengembalianRepository extends TransSuratKeteranganPengembalian
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_piutang', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('ud')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_piutang', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('ud')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
