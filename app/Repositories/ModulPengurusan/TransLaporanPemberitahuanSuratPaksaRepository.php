<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransLaporanPemberitahuanSuratPaksa;

class TransLaporanPemberitahuanSuratPaksaRepository extends TransLaporanPemberitahuanSuratPaksa
{
    public function deleteByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_piutang', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerja(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_piutang', function($subQuery) use ($idSatuanKerjaKPKNL){
            $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
