<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransDebitur;

class TransDebiturRepository extends TransDebitur
{
    /**
     * Digunakan untuk melakukan penghapusan data trans piutang
     * @return mixed
     */
    public function deleteByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function ($subQuery) use ($idSatuanKerjaKPKNL) {
            $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
        })
            ->forceDelete();
    }

    /**
     * Digunakan untuk mendapatkan trans piutang berdasarkan id satuan kerja KPKNL
     * @param int $idSatuanKerjaKPKNL
     * @return mixed
     */
    public function getByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function ($subQuery) use ($idSatuanKerjaKPKNL) {
            $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
