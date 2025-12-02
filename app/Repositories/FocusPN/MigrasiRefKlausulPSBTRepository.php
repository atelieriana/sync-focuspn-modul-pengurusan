<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiRefKlausulPSBDT;

class MigrasiRefKlausulPSBTRepository extends MigrasiRefKlausulPSBDT
{
    /**
     * Get klausul PSBDT berdasarkan kode satuan kerja
     * @param string $kodeSatuanKerja
     * @return mixed
     */
    public function getByKodeSatuanKerja(string $kodeSatuanKerja)
    {
        return self::select(
            'ID_REF_SATUAN_KERJA_KPKNL',
            'KLAUSUL_PSBDT',
            'CREATED_BY',
            'CREATED_AT',
            'UPDATED_BY',
            'UPDATED_AT'
        )
            ->where('KODE_SATUAN_KERJA', $kodeSatuanKerja)
            ->get();
    }
}
