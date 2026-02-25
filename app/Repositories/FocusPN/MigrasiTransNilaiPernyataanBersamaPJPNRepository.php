<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiTransNilaiPernyataanBersamaPJPN;

class MigrasiTransNilaiPernyataanBersamaPJPNRepository extends MigrasiTransNilaiPernyataanBersamaPJPN
{
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL)
            ->get();
    }
}
