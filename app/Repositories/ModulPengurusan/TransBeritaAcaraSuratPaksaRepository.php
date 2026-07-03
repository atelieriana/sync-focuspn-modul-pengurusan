<?php

namespace App\Repositories\ModulPengurusan;

use App\Models\ModulPengurusan\TransBeritaAcaraSuratPaksa;

class TransBeritaAcaraSuratPaksaRepository extends TransBeritaAcaraSuratPaksa
{
    /**
     * Melakukan penghapusan data trans berita acara surat paksa berdasarkan ID satuan kerja KPKNL pada Trans Piutang
     */
    public function deleteByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_piutang', function($subQuery) use ($idSatuanKerjaKPKNL) {
            return $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->forceDelete();
    }

    /**
     * Melakukan penghapusan data trans berita acara surat paksa berdasarkan ID satuan kerja KPKNL pada Trans Piutang
     */
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('id_trans_piutang', function($subQuery) use ($idSatuanKerjaKPKNL) {
            return $subQuery->select('id')
                ->from('trans_piutang')
                ->where('id_ref_satuan_kerja_kpknl', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
