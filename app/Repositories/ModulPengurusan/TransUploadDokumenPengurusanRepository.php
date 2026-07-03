<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransUploadDokumenPengurusan;

class TransUploadDokumenPengurusanRepository extends TransUploadDokumenPengurusan
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_tahap_pengurusan', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('id')
                ->from('trans_tahap_pengurusan')
                ->whereIn('id_trans_piutang', function ($subQuery) use ($idSatuanKerjaKPKNL){
                    $subQuery->select('id')
                        ->from('trans_piutang')
                        ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
                });
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_tahap_pengurusan', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('id')
                ->from('trans_tahap_pengurusan')
                ->whereIn('id_trans_piutang', function ($subQuery) use ($idSatuanKerjaKPKNL){
                    $subQuery->select('id')
                        ->from('trans_piutang')
                        ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
                });
        })
            ->get();
    }
}
