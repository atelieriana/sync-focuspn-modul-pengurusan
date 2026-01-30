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
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerjaKPKNL) {
            return $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
        })
            ->forceDelete();
    }

    /**
     * Melakukan penghapusan data trans berita acara surat paksa berdasarkan ID satuan kerja KPKNL pada Trans Piutang
     */
    public function getByIdSatuanKerjaKPKNL(int $idSatuanKerjaKPKNL)
    {
        return self::whereIn('ID_TRANS_PIUTANG', function($subQuery) use ($idSatuanKerjaKPKNL) {
            return $subQuery->select('ID')
                ->from('TRANS_PIUTANG')
                ->where('ID_REF_SATUAN_KERJA_KPKNL', $idSatuanKerjaKPKNL);
        })
            ->get();
    }
}
