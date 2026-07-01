<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransPernyataanPiutangTelahOptimal;

class TransPernyataanPiutangTelahOptimalRepository extends TransPernyataanPiutangTelahOptimal
{
    public function deleteByIdSatuanKerjaKPKNLAndJenisPernyataan(int $idSatuanKerjaKPKNL, int $idJenisPernyataan)
    {
        return self::where('id_ref_jenis_pernyataan_piutang_telah_optimal', $idJenisPernyataan)
            ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNLAndJenisPernyataan(int $idSatuanKerjaKPKNL, int $idJenisPernyataan)
    {
        return self::where('id_ref_jenis_pernyataan_piutang_telah_optimal', $idJenisPernyataan)
            ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL)
            ->get();
    }
}
