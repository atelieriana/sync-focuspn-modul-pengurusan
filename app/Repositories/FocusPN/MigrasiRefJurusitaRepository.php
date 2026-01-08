<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiRefJurusita;

class MigrasiRefJurusitaRepository extends MigrasiRefJurusita
{
    public function getByIdSatuanKerjaKPKNL(int $kodeSatuanKerja)
    {
        return self::where('KODE_SATUAN_KERJA', $kodeSatuanKerja)->get();
    }
}