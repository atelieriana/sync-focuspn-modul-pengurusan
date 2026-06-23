<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransKoreksi;

class TransKoreksiRepository extends TransKoreksi
{
    public function deleteByIdSatuanKerja(int $idSatuanKerja)
    {
        return self::whereIn('id_trans_piutang', function($subQuery) use ($idSatuanKerja) {
            return $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerja);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerja(int $idSatuanKerja)
    {
        return self::whereIn('id_trans_piutang', function($subQuery) use ($idSatuanKerja) {
            return $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerja);
        })
            ->get();
    }
}
