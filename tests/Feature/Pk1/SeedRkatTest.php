<?php

use App\Actions\Rkat\AjukanRkat;
use App\Models\RkatValue;
use App\Models\RkatVersion;
use Database\Seeders\RkatSeeder;
use Tests\Concerns\RkatFixture;

it('seed RKAT 2026 idempoten dan lulus VAL-1..VAL-6', function () {
    RkatFixture::siapkan();

    $this->seed(RkatSeeder::class);
    $this->seed(RkatSeeder::class);

    $v = RkatVersion::firstOrFail();
    expect(RkatVersion::count())->toBe(1)
        ->and($v->status)->toBe('draft')
        ->and(RkatValue::count())->toBe(collect(RkatFixture::fixture()['pos'])->where('agregasi', 'input_langsung')->count())
        ->and(collect(AjukanRkat::evaluasiVersi($v))->pluck('lulus')->all())->toBe(array_fill(0, 6, true));
});
