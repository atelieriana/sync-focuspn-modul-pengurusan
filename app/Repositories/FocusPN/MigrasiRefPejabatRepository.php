<?php

namespace App\Repositories\FocusPN;

use App\Models\FocusPN\MigrasiRefPejabat;

class MigrasiRefPejabatRepository extends MigrasiRefPejabat
{
    public function getByKodeSatuanKerja($kodeSatuanKerja)
    {
        return self::select(
            'ID_REF_SATUAN_KERJA',
            'ID_REF_JABATAN',
            'NAMA',
            'NIP',
            'TELEPON',
            'EMAIL',
            'JENIS_KELAMIN',
            'NOMOR_SK_PENGANGKATAN',
            'TANGGAL_SK_PENGANGKATAN',
            'PERIHAL_SK_PENGANGKATAN',
            'CREATED_BY',
            'CREATED_AT',
            'UPDATED_BY',
            'UPDATED_AT'
        )
            ->where('KODE_SATUAN_KERJA', $kodeSatuanKerja)
            ->get();
    }
}
