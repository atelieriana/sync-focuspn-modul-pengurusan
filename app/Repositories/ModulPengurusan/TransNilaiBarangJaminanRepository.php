<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransNilaiBarangJaminan;

class TransNilaiBarangJaminanRepository extends TransNilaiBarangJaminan
{
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL, int $idRefJenisPenilaian)
    {
        return self::where('id_ref_jenis_penilaian', $idRefJenisPenilaian)
            ->whereIn('id_trans_barang_jaminan', function($subQuery) use($idSatuanKerjaKPKNL) {
            return $subQuery->select('id')
                ->from('trans_barang_jaminan')
                ->whereIn('id_trans_piutang', function ($subQuery) use($idSatuanKerjaKPKNL) {
                    return $subQuery->select('id')
                        ->from('trans_piutang')
                        ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
                });
        })
            ->forceDelete();
    }

    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL, int $idRefJenisPenilaian)
    {
        return self::where('id_ref_jenis_penilaian', $idRefJenisPenilaian)
            ->whereIn('id_trans_barang_jaminan', function($subQuery) use($idSatuanKerjaKPKNL) {
            return $subQuery->select('id')
                ->from('trans_barang_jaminan')
                ->whereIn('id_trans_piutang', function ($subQuery) use($idSatuanKerjaKPKNL) {
                    return $subQuery->select('id')
                        ->from('trans_piutang')
                        ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
                });
        })
            ->get();
    }
}
