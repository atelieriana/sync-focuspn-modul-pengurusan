<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransPenjaminHutang;

class MigrasiTransPenjaminHutangRepository extends MigrasiTransPenjaminHutang
{
    /**
     * Digunakan untuk mendapatkan data migrasi trans penjamin hutang berdasarkan id satuan kerja
     *
     * @param int $idSatuanKerja
     * @return mixed
     */
    public function getByIdSatuanKerja(int $idSatuanKerja)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja)
            ->get();
    }
}
