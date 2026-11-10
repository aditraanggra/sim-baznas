<?php

use App\Models\RkatValue;
use App\Models\User;
use App\Services\Rkat\BandingkanRkat;
use App\Services\Rkat\HitungRkat;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\RkatFixture;

beforeEach(function () {
    $this->withoutVite();
    RkatFixture::siapkan();
});

function masukBandingkan(string $peran): User
{
    $user = User::factory()->create();
    $user->assignRole($peran);
    test()->actingAs($user);

    return $user;
}

function barisBandingkan(array $hasil, string $kode): array
{
    return collect($hasil['baris'])->firstWhere('kode', $kode);
}

it('FR-R-07: selisih dan persen antar dua versi satu tahun, termasuk baris induk', function () {
    $b = RkatFixture::versi(2026, 'draft', ['L2/1.1' => 40_000_000]);
    $b->forceFill(['jenis' => 'perubahan', 'nomor_perubahan' => 1])->save();
    $a = RkatFixture::versi(2026);

    $hasil = BandingkanRkat::bandingkan($a, $b);
    $daun = barisBandingkan($hasil, 'L2/1.1');
    $induk = barisBandingkan($hasil, 'L2/1');

    expect($daun['nilai_a'])->toBe(35_000_000)
        ->and($daun['nilai_b'])->toBe(40_000_000)
        ->and($daun['selisih'])->toBe(5_000_000)
        ->and($daun['persen'])->toBe(5_000_000 * 100 / 35_000_000)
        ->and($induk['nilai_a'])->toBe(10_603_520_000)
        ->and($induk['selisih'])->toBe(5_000_000)
        ->and($induk['persen'])->toBe(5_000_000 * 100 / 10_603_520_000)
        ->and(barisBandingkan($hasil, 'L2/1.2')['selisih'])->toBe(0);
});

it('FR-R-07: selisih dan persen antar dua tahun lewat halaman, versi acuan Disahkan', function () {
    $a = RkatFixture::versi(2025, 'disahkan');
    RkatFixture::versi(2026, 'draft', ['L2/1.1' => 1])->forceFill(['jenis' => 'perubahan', 'nomor_perubahan' => 1])->save();
    $b = RkatFixture::versi(2026, 'diajukan', ['L2/1.1' => 17_500_000]);
    masukBandingkan('pimpinan');

    $this->get(route('pkp.rencana.bandingkan', ['tahun_a' => 2025, 'tahun_b' => 2026]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('pkp/rencana/bandingkan')
            ->where('mode', 'tahun')
            ->where('a.versi.id', $a->id)
            ->where('a.bukan_disahkan', false)
            ->where('b.versi.id', $b->id)
            ->where('b.bukan_disahkan', true)
            ->where('hasil.baris', fn ($baris) => collect($baris)->firstWhere('kode', 'L2/1.1')['selisih'] === -17_500_000
                && collect($baris)->firstWhere('kode', 'L2/1.1')['persen_tampil'] === '-50,0%'));
});

it('BR-12: persen pembanding dari nilai asli; nilai A nol memberi n/a', function () {
    $a = RkatFixture::versi(2025, 'draft', ['L2/1.1' => 3, 'L2/1.2' => 0]);
    $b = RkatFixture::versi(2026, 'draft', ['L2/1.1' => 4, 'L2/1.2' => 10]);

    $hasil = BandingkanRkat::bandingkan($a, $b);

    expect(barisBandingkan($hasil, 'L2/1.1')['persen'])->toBe(1 * 100 / 3)
        ->and(barisBandingkan($hasil, 'L2/1.1')['persen_tampil'])->toBe('33,3%')
        ->and(barisBandingkan($hasil, 'L2/1.2')['persen'])->toBeNull()
        ->and(barisBandingkan($hasil, 'L2/1.2')['persen_tampil'])->toBe('n/a');
});

it('BR-10: pos tak terpetakan tidak ikut total pembanding', function () {
    $a = RkatFixture::versi(2025);
    $b = RkatFixture::versi(2026);
    $sebelum = BandingkanRkat::bandingkan($a, $b);

    RkatValue::create([
        'rkat_version_id' => $b->id, 'pos_anggaran_id' => null, 'kode_asal' => 'L2/9.9',
        'nama_asal' => 'Pos lama', 'tipe_nilai' => 'rupiah', 'nilai' => 999_000_000,
    ]);
    $sesudah = BandingkanRkat::bandingkan($a, $b->fresh());

    expect($sesudah['baris'])->toBe($sebelum['baris'])
        ->and(barisBandingkan($sesudah, 'L2/1')['nilai_b'])->toBe(HitungRkat::untukVersi($a)['per_kode']['L2/1'])
        ->and($sesudah['tak_terpetakan_a'])->toBe(['jumlah' => 0, 'kode' => []])
        ->and($sesudah['tak_terpetakan_b'])->toBe(['jumlah' => 1, 'kode' => ['L2/9.9']]);

    masukBandingkan('keuangan');
    $this->get(route('pkp.rencana.bandingkan', ['versi_a' => $a->id, 'versi_b' => $b->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('mode', 'versi')
            ->where('hasil.tak_terpetakan_b.jumlah', 1)
            ->where('hasil.tak_terpetakan_b.kode', ['L2/9.9']));
});

it('FR-R-07: halaman tanpa parameter hanya memuat pemilih; peran pimpinan boleh melihat', function () {
    RkatFixture::versi(2025);
    $arsip = RkatFixture::versi(2024);
    $arsip->forceFill(['arsip' => true])->save();
    masukBandingkan('pimpinan');

    $this->get(route('pkp.rencana.bandingkan'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('pkp/rencana/bandingkan')
            ->where('a', null)
            ->where('b', null)
            ->where('hasil', null)
            ->has('daftar_versi', 1));
});

it('FR-R-07: tahun tanpa versi memberi galat, bukan hasil; tamu diarahkan login', function () {
    $a = RkatFixture::versi(2025);
    masukBandingkan('pelaporan');

    $this->get(route('pkp.rencana.bandingkan', ['versi_a' => $a->id, 'tahun_b' => 1999]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('hasil', null)->where('b.galat', 'Tahun 1999 belum punya versi RKAT.'));

    auth()->logout();
    $this->get(route('pkp.rencana.bandingkan'))->assertRedirect(route('login'));
});
