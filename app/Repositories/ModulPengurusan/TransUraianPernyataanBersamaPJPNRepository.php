<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransUraianPernyataanBersamaPJPN;

class TransUraianPernyataanBersamaPJPNRepository extends TransUraianPernyataanBersamaPJPN
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
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerja){
            $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerja);
        })
            ->get();
    }
}
