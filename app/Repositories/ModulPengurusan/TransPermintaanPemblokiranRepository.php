<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPermintaanPemblokiran;

class TransPermintaanPemblokiranRepository extends TransPermintaanPemblokiran
{
    public function deleteByIdSatuanKerja(int $idSatuanKerja)
    {
        return self::whereIn('id_trans_piutang', function($subQuery) use ($idSatuanKerja){
            $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerja);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerja(int $idSatuanKerja)
    {
        return self::whereIn('id_trans_piutang', function($subQuery) use ($idSatuanKerja){
            $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerja);
        })
            ->get();
    }
}
