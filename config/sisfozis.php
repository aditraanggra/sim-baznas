<?php

return [
    'bidang' => [
        'pengumpulan' => [
            'kode' => 'pengumpulan',
            'nama' => 'Pengumpulan',
            'warna' => '#259148',
            'urutan' => 1,
        ],
        'distribusi' => [
            'kode' => 'distribusi',
            'nama' => 'Pendistribusian dan Pendayagunaan',
            'warna' => '#FDC727',
            'urutan' => 2,
        ],
        'umum' => [
            'kode' => 'umum',
            'nama' => 'SDM, Administrasi dan Umum',
            'warna' => '#1D4ED8',
            'urutan' => 3,
        ],
        'pkp' => [
            'kode' => 'pkp',
            'nama' => 'Perencanaan, Keuangan dan Pelaporan',
            'warna' => '#5B21B6',
            'urutan' => 4,
        ],
    ],

    'peran' => [
        'pelayanan_muzakki' => ['nama' => 'Pelayanan Muzakki', 'bidang_utama' => 'pengumpulan', 'internal' => true],
        'pelayanan_mustahik' => ['nama' => 'Pelayanan Mustahik', 'bidang_utama' => 'distribusi', 'internal' => true],
        'admin_arsip' => ['nama' => 'Admin Arsip', 'bidang_utama' => 'umum', 'internal' => true],
        'perencanaan' => ['nama' => 'Staff Perencanaan', 'bidang_utama' => 'pkp', 'internal' => true],
        'keuangan' => ['nama' => 'Staff Keuangan', 'bidang_utama' => 'pkp', 'internal' => true],
        'kabag_sau' => ['nama' => 'Kepala Bagian SAU', 'bidang_utama' => 'umum', 'internal' => true],
        'kabag_pengumpulan' => ['nama' => 'Kepala Bagian Pengumpulan', 'bidang_utama' => 'pengumpulan', 'internal' => true],
        'kabag_pendis_pendag' => ['nama' => 'Kepala Bagian Pendis Pendag', 'bidang_utama' => 'distribusi', 'internal' => true],
        'kabag_pkp' => ['nama' => 'Kepala Bagian PKP', 'bidang_utama' => 'pkp', 'internal' => true],
        'staf_pendistribusian' => ['nama' => 'Staff Pendistribusian', 'bidang_utama' => 'distribusi', 'internal' => true],
        'staf_pendayagunaan' => ['nama' => 'Staff Pendayagunaan', 'bidang_utama' => 'distribusi', 'internal' => true],
        'amil' => ['nama' => 'Amil', 'bidang_utama' => 'umum', 'internal' => true],
        'waka_1' => ['nama' => 'Wakil Ketua I', 'bidang_utama' => 'pengumpulan', 'internal' => true],
        'waka_2' => ['nama' => 'Wakil Ketua II', 'bidang_utama' => 'distribusi', 'internal' => true],
        'waka_3' => ['nama' => 'Wakil Ketua III', 'bidang_utama' => 'pkp', 'internal' => true],
        'waka_4' => ['nama' => 'Wakil Ketua IV', 'bidang_utama' => 'umum', 'internal' => true],
        'ketua' => ['nama' => 'Ketua', 'bidang_utama' => null, 'internal' => true],
        'upz' => ['nama' => 'Pengurus UPZ', 'bidang_utama' => 'umum', 'internal' => false, 'cakupan' => 'upz_sendiri'],
        'admin' => ['nama' => 'Administrator Sistem', 'bidang_utama' => null, 'internal' => true, 'admin_sistem' => true],
    ],

    'matriks_peran' => [
        'admin' => [
            'pengumpulan' => ['view', 'create', 'update', 'delete', 'approve', 'print', 'export'],
            'distribusi' => ['view', 'create', 'update', 'delete', 'approve', 'print', 'export'],
            'umum' => ['view', 'create', 'update', 'delete', 'approve', 'print', 'export'],
            'pkp' => ['view', 'create', 'update', 'delete', 'approve', 'print', 'export'],
        ],
        'ketua' => [
            'pengumpulan' => ['view', 'approve', 'print', 'export'],
            'distribusi' => ['view', 'approve', 'print', 'export'],
            'umum' => ['view', 'approve', 'print', 'export'],
            'pkp' => ['view', 'approve', 'print', 'export'],
        ],
        'waka_1' => [
            'pengumpulan' => ['view', 'approve', 'print', 'export'],
            'distribusi' => ['view'],
            'umum' => ['view'],
            'pkp' => ['view'],
        ],
        'waka_2' => [
            'pengumpulan' => ['view'],
            'distribusi' => ['view', 'approve', 'print', 'export'],
            'umum' => ['view'],
            'pkp' => ['view'],
        ],
        'waka_3' => [
            'pengumpulan' => ['view'],
            'distribusi' => ['view'],
            'umum' => ['view'],
            'pkp' => ['view', 'approve', 'print', 'export'],
        ],
        'waka_4' => [
            'pengumpulan' => ['view'],
            'distribusi' => ['view'],
            'umum' => ['view', 'approve', 'print', 'export'],
            'pkp' => ['view'],
        ],
        'kabag_pengumpulan' => [
            'pengumpulan' => ['view', 'create', 'update', 'delete', 'approve', 'print', 'export'],
            'distribusi' => ['view'],
            'umum' => ['view'],
            'pkp' => ['view'],
        ],
        'kabag_pendis_pendag' => [
            'pengumpulan' => ['view'],
            'distribusi' => ['view', 'create', 'update', 'delete', 'approve', 'print', 'export'],
            'umum' => ['view'],
            'pkp' => ['view'],
        ],
        'kabag_pkp' => [
            'pengumpulan' => ['view'],
            'distribusi' => ['view'],
            'umum' => ['view'],
            'pkp' => ['view', 'create', 'update', 'delete', 'approve', 'print', 'export'],
        ],
        'kabag_sau' => [
            'pengumpulan' => ['view'],
            'distribusi' => ['view'],
            'umum' => ['view', 'create', 'update', 'delete', 'approve', 'print', 'export'],
            'pkp' => ['view'],
        ],
        'pelayanan_muzakki' => [
            'pengumpulan' => ['view', 'create', 'update', 'print', 'export'],
        ],
        'pelayanan_mustahik' => [
            'distribusi' => ['view', 'create', 'update', 'print', 'export'],
        ],
        'staf_pendistribusian' => [
            'distribusi' => ['view', 'create', 'update', 'print', 'export'],
        ],
        'staf_pendayagunaan' => [
            'distribusi' => ['view', 'create', 'update', 'print', 'export'],
        ],
        'admin_arsip' => [
            'umum' => ['view', 'create', 'update', 'print', 'export'],
        ],
        'perencanaan' => [
            'pkp' => ['view', 'create', 'update', 'print', 'export'],
            'pengumpulan' => ['view'],
            'distribusi' => ['view'],
        ],
        'keuangan' => [
            'pkp' => ['view', 'create', 'update', 'print', 'export'],
            'pengumpulan' => ['view'],
            'distribusi' => ['view'],
            'umum' => ['view'],
        ],
        'amil' => [
            'umum' => ['view'],
        ],
        'upz' => [
            'umum' => ['view'],
        ],
        // Alias peran legacy PK0/PK1
        'pimpinan' => [
            'pengumpulan' => ['view', 'approve', 'print', 'export'],
            'distribusi' => ['view', 'approve', 'print', 'export'],
            'umum' => ['view', 'approve', 'print', 'export'],
            'pkp' => ['view', 'approve', 'print', 'export'],
        ],
        'pelaporan' => [
            'pkp' => ['view', 'create', 'update', 'print', 'export'],
        ],
    ],

    'aktifkan_2fa_login' => env('SISFOZIS_2FA_LOGIN', false),
    'paksa_2fa_middleware' => false,
    'sandi_kuat_ketat' => false,

    'kop_surat' => [
        'lembaga' => 'BADAN AMIL ZAKAT NASIONAL (BAZNAS)',
        'kabupaten' => 'KABUPATEN CIANJUR',
        'alamat' => 'Jl. Raya Bandung KM. 03 Sadewata, Kec. Karangtengah, Kabupaten Cianjur, Jawa Barat 43281',
        'telepon' => '(0263) 264866',
        'email' => 'baznaskab.cianjur@baznas.go.id',
        'website' => 'https://baznaskabcianjur.com',
        'kota' => 'Cianjur',
    ],
];
