<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\TGlobal;

class TGlobalRepository extends TGlobal
{
    /**
     * Digunakan untuk mendapatkan TGlobal berdasarkan kode KPKNL
     * @param int $kodeKPKNL
     * @return void
     */
    public function getTGlobalByKPKNL(int $kodeKPKNL)
    {
        return $this->select(
            'KODE_KPKNL',
            'NAMA',

        )
            ->where('KODE_KPKNL', $kodeKPKNL);
    }
}
