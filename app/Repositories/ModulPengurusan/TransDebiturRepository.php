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
        return self::whereIn('id_trans_piutang', function ($subQuery) use ($idSatuanKerjaKPKNL) {
            $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
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
        return self::whereIn('id_trans_piutang', function ($subQuery) use ($idSatuanKerjaKPKNL) {
            $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
