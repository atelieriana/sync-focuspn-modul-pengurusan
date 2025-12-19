<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPenjaminHutang;

class TransPenjaminHutangRepository extends TransPenjaminHutang
{
    /**
     * Digunakan untuk menghapus trans penjamin hutang
     *
     * @param int $idSatuanKerjaKPKNL
     * @return mixed
     */
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerjaKPKNL) {
            $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
        })
            ->forceDelete();
    }

    /**
     * Digunakan untuk mendapatkan trans penjamin hutang berdasar id satuan kerja kpknl
     *
     * @param int $idSatuanKerjaKPKNL
     * @return mixed
     */
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerjaKPKNL) {
            $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
