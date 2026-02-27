<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPermintaanPemblokiran;

class TransPermintaanPemblokiranRepository extends TransPermintaanPemblokiran
{
    public function deleteByIdSatuanKerja(int $idSatuanKerja)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerja){
            $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerja(int $idSatuanKerja)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerja){
            $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja);
        })
            ->get();
    }
}
