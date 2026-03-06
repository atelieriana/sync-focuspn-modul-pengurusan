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
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $kodeSatuanKerja)
            ->get();
    }
}
