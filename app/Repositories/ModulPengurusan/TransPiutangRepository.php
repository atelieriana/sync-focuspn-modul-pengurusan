<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPiutang;

class TransPiutangRepository extends TransPiutang
{
    /**
     * @param int $idSatuanKerjaKPKNL
     * @return mixed
     */
    public function deleteByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
            ->forceDelete();
    }

    /**
     * Digunakan untuk mendapatkan ID Satuan Kerja KPKNL
     * @param int $idSatuanKerjaKPKNL
     * @return mixed
     */
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)->get();
    }
}
