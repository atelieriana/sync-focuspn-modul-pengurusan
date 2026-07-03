<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransDebiturBadanHukum;

class TransDebiturBadanHukumRepository extends TransDebiturBadanHukum
{
    /**
     * Digunakan untuk menghapusa data debitur perseorangan berdasarkan id satuan kerja kpknl
     *
     * @param $idSatuanKerjaKPKNL
     * @return mixed
     */
    public function deleteByIdSatuanKerjaKPKNL($idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_debitur', function ($subQuery) use ($idSatuanKerjaKPKNL) {
            $subQuery->select('id')
                ->from('trans_debitur')
                ->whereIn('id_trans_piutang', function ($subQuery) use ($idSatuanKerjaKPKNL){
                    $subQuery->select('id')
                        ->from('trans_piutang')
                        ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
                });
        })
            ->forceDelete();
    }

    /**
     * Digunakan untuk mendapatkan data trans debitur perseorangan berdasarkan id satuan kerja kpknl
     *
     * @param $idSatuanKerjaKPKNL
     * @return mixed
     */
    public function getByIdSatuanKerjaKPKNL($idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_debitur', function ($subQuery) use ($idSatuanKerjaKPKNL) {
            $subQuery->select('id')
                ->from('trans_debitur')
                ->whereIn('id_trans_piutang', function ($subQuery) use ($idSatuanKerjaKPKNL){
                    $subQuery->select('id')
                        ->from('trans_piutang')
                        ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
                });
        })
            ->get();
    }
}
