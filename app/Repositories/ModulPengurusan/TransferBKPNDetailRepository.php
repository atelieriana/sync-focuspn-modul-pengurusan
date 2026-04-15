<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransferBKPNDetail;

class TransferBKPNDetailRepository extends TransferBKPNDetail
{
    public function deleteByIdSatuanKerjaKPKNLAsal(int $idSatuanKerjaKPKNLAsal)
    {
        return self::whereIn('ID_TRANSFER_BKPN', function($subQuery) use ($idSatuanKerjaKPKNLAsal){
            return $subQuery->select('ID')
                ->from('TRANSFER_BKPN')
                ->where('ID_SATUAN_KERJA_KPKNL_ASAL', $idSatuanKerjaKPKNLAsal);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNLAsal(int $idSatuanKerjaKPKNLAsal)
    {
        return self::whereIn('ID_TRANSFER_BKPN', function($subQuery) use ($idSatuanKerjaKPKNLAsal){
            return $subQuery->select('ID')
                ->from('TRANSFER_BKPN')
                ->where('ID_SATUAN_KERJA_KPKNL_ASAL', $idSatuanKerjaKPKNLAsal);
        })
            ->get();
    }
}
