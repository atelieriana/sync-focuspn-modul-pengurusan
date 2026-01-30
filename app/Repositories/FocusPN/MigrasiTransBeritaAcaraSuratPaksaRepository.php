<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransBeritaAcaraSuratPaksa;

class MigrasiTransBeritaAcaraSuratPaksaRepository extends MigrasiTransBeritaAcaraSuratPaksa
{
    /**
     * Digunakan untuk mendapatkan data trans berita acara 
     */
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
