<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPernyataanPiutangTelahOptimalNominal;

class TransPernyataanPiutangTelahOptimalNominalRepository extends TransPernyataanPiutangTelahOptimalNominal
{
    public function deleteByIdSatuanKerjaKPKNLAndJenisPernyataan(int $idSatuanKerjaKPKNL, int $idJenisPernyataan)
    {
        return self::whereIn('id_trans_pernyataan_piutang_telah_optimal', function($subQuery) use ($idSatuanKerjaKPKNL, $idJenisPernyataan){
            $subQuery->select('id')
                ->from('trans_pernyataan_piutang_telah_optimal')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
                ->where('id_ref_jenis_pernyataan_piutang_telah_optimal', $idJenisPernyataan);
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNLAndJenisPernyataan(int $idSatuanKerjaKPKNL, int $idJenisPernyataan)
    {
        return self::whereIn('id_trans_pernyataan_piutang_telah_optimal', function($subQuery) use ($idSatuanKerjaKPKNL, $idJenisPernyataan){
            $subQuery->select('id')
                ->from('trans_pernyataan_piutang_telah_optimal')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
                ->where('id_ref_jenis_pernyataan_piutang_telah_optimal', $idJenisPernyataan);
        })
            ->get();
    }
}
