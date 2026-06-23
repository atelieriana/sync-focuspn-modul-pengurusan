<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransferBKPNDetail;

class TransferBKPNDetailRepository extends TransferBKPNDetail
{
    public function deleteByIdSatuanKerjaKPKNLAsal(int $idSatuanKerjaKPKNLAsal)
    {
        return self::whereIn('id_transfer_bkpn', function($subQuery) use ($idSatuanKerjaKPKNLAsal){
            return $subQuery->select('id')
                ->from('transfer_bkpn')
                ->where('id_satuan_kerja_kpknl_asal', $idSatuanKerjaKPKNLAsal);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNLAsal(int $idSatuanKerjaKPKNLAsal)
    {
        return self::whereIn('id_transfer_bkpn', function($subQuery) use ($idSatuanKerjaKPKNLAsal){
            return $subQuery->select('id')
                ->from('transfer_bkpn')
                ->where('id_satuan_kerja_kpknl_asal', $idSatuanKerjaKPKNLAsal);
        })
            ->get();
    }
}
