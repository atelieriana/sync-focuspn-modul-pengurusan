<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransDebiturPerseorangan;

class TransDebiturPerseoranganRepository extends TransDebiturPerseorangan
{
    /**
     * Digunakan untuk menghapusa data debitur perseorangan berdasarkan id satuan kerja kpknl
     *
     * @param $idSatuanKerjaKPKNL
     * @return mixed
     */
    public function deleteByIdSatuanKerjaKPKNL($idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_DEBITUR', function ($subQuery) use ($idSatuanKerjaKPKNL) {
            $subQuery->select('ID')
                ->from('TRANS_DEBITUR')
                ->whereIn('ID_TRANS_PIUTANG', function ($subQuery) use ($idSatuanKerjaKPKNL){
                    $subQuery->select('ID')
                        ->from('TRANS_PIUTANG')
                        ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
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
        return self::whereIn('ID_TRANS_DEBITUR', function ($subQuery) use ($idSatuanKerjaKPKNL) {
            $subQuery->select('ID')
                ->from('TRANS_DEBITUR')
                ->whereIn('ID_TRANS_PIUTANG', function ($subQuery) use ($idSatuanKerjaKPKNL){
                    $subQuery->select('ID')
                        ->from('TRANS_PIUTANG')
                        ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
                });
        })
            ->get();
    }
}
