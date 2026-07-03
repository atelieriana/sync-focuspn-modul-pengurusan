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
        return self::whereIn('id_trans_piutang', function($subQuery) use ($idSatuanKerjaKPKNL) {
            $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
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
        return self::whereIn('id_trans_piutang', function($subQuery) use ($idSatuanKerjaKPKNL) {
            $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
