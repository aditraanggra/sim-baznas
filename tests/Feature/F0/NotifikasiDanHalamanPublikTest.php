<?php

use App\Models\Notifikasi;
use App\Models\User;
use App\Services\NotifikasiService;
use Database\Seeders\PeranSeeder;

beforeEach(function () {
    $this->seed(PeranSeeder::class);
});

it('F0-24: NotifikasiService mengirim notifikasi ke peran dan pengguna dapat menandai dibaca', function () {
    $ketua = User::factory()->create()->assignRole(['ketua', 'amil']);
    $staf = User::factory()->create()->assignRole(['pelayanan_mustahik', 'amil']);

    $service = app(NotifikasiService::class);
    $terkirim = $service->kirimKePeran(
        'ketua',
        'disposisi_masuk',
        'Permohonan Baru Menunggu Disposisi',
        'Permohonan TRK-2026-AB12 menunggu disposisi Anda.',
        '/distribusi/disposisi-saya',
        'permohonan',
        15,
    );

    expect($terkirim)->toHaveCount(1);
    $notif = $terkirim->first();
    expect($notif->user_id)->toBe($ketua->id)
        ->and($notif->dibaca_at)->toBeNull();

    // Pengguna lain dilarang menandai notifikasi milik ketua sebagai dibaca.
    $this->actingAs($staf)
        ->post(route('notifikasi.baca', $notif))
        ->assertForbidden();

    // Ketua menandai dibaca dan diarahkan ke tautan terkait.
    $this->actingAs($ketua)
        ->post(route('notifikasi.baca', $notif))
        ->assertRedirect('/distribusi/disposisi-saya');

    expect($notif->fresh()->dibaca_at)->not->toBeNull();

    // Tambah 2 notifikasi lagi lalu tandai semua dibaca.
    $service->kirimKeUser($ketua, 'sk_upz_h_30', 'SK Segera Habis', 'SK UPZ DKM Agung habis dalam 30 hari.');
    $service->kirimKeUser($ketua, 'pembatalan', 'Pengajuan Pembatalan', 'Ada pengajuan pembatalan kwitansi.');
    expect(Notifikasi::where('user_id', $ketua->id)->belumDibaca()->count())->toBe(2);

    $this->actingAs($ketua)
        ->post(route('notifikasi.baca-semua'))
        ->assertRedirect();

    expect(Notifikasi::where('user_id', $ketua->id)->belumDibaca()->count())->toBe(0);
});
