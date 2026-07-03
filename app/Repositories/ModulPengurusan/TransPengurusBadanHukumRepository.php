<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPengurusBadanHukum;

class TransPengurusBadanHukumRepository extends TransPengurusBadanHukum
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerja)
    {
        return self::whereIn('id_trans_piutang', function ($subQuery) use ($idSatuanKerja) {
            $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerja);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerja)
    {
        return self::whereIn('id_trans_piutang', function ($subQuery) use ($idSatuanKerja) {
            $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerja);
        })
            ->get();
    }
}
