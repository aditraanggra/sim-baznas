<?php

return [
    'lembaga' => env('PENOMORAN_LEMBAGA', 'BAZNAS-CJR'),

    'kode_jenis' => [
        'kwitansi' => 'KW',
        'penerimaan' => 'PNR',
        'ba_penerimaan' => 'BA-PNR',
        'permohonan' => 'PRM',
        'penyaluran' => 'PNY',
        'ba_penyaluran' => 'BA-PNY',
        'disposisi' => 'DSP',
        'surat_masuk' => 'AGD',
        'surat_keluar' => 'SKL',
        'aset' => 'AST',
        'muzakki' => 'MZK',
        'mustahik' => 'MST',
        'upz' => 'UPZ',
    ],

    // Format nomor dapat diganti sewaktu-waktu tanpa mengubah kode Action atau database (K15).
    // Token: {urut}, {urut:N}, {jenis}, {lembaga}, {tahun}, {tahun_pendek}, {bulan}, {romawi_bulan}, {tanggal}, {bidang}, {kode}
    'format' => [
        'kwitansi' => 'KW/{lembaga}/{tahun}/{romawi_bulan}/{urut:5}',
        'penerimaan' => 'PNR/{lembaga}/{tahun}/{romawi_bulan}/{urut:5}',
        'ba_penerimaan' => 'BA-PNR/{lembaga}/{tahun}/{romawi_bulan}/{urut:4}',
        'permohonan' => 'PRM/{lembaga}/{tahun}/{romawi_bulan}/{urut:4}',
        'penyaluran' => 'PNY/{lembaga}/{tahun}/{romawi_bulan}/{urut:4}',
        'ba_penyaluran' => 'BA-PNY/{lembaga}/{tahun}/{romawi_bulan}/{urut:4}',
        'disposisi' => 'DSP/{lembaga}/{tahun}/{romawi_bulan}/{urut:4}',
        'surat_masuk' => 'AGD/{lembaga}/{tahun}/{romawi_bulan}/{urut:4}',
        'surat_keluar' => '{urut:3}/{kode}/{lembaga}/{romawi_bulan}/{tahun}',
        'aset' => 'AST/{kode}/{tahun}/{urut:4}',
        'muzakki' => 'MZK-{tahun}-{urut:5}',
        'mustahik' => 'MST-{tahun}-{urut:5}',
        'upz' => 'UPZ-{tahun}-{urut:4}',
        'default' => '{jenis}/{lembaga}/{tahun}/{romawi_bulan}/{urut:4}',
    ],
];
