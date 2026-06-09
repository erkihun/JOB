<?php

declare(strict_types=1);

use App\Services\EthiopianCalendar;
use Carbon\Carbon;

test('fromGregorian converts known anchor dates correctly', function (int $gy, int $gm, int $gd, array $expected): void {
    expect(EthiopianCalendar::fromGregorian($gy, $gm, $gd))->toBe($expected);
})->with([
    // Ethiopian millennium: 2007-09-12 GC = Meskerem 1, 2000 ET
    'millennium new year' => [2007, 9, 12, ['year' => 2000, 'month' => 1, 'day' => 1]],
    // 2024-09-11 GC = Meskerem 1, 2017 ET
    '2017 new year' => [2024, 9, 11, ['year' => 2017, 'month' => 1, 'day' => 1]],
    // 2023-09-12 GC = Meskerem 1, 2016 ET
    '2016 new year' => [2023, 9, 12, ['year' => 2016, 'month' => 1, 'day' => 1]],
    // 2023-09-11 GC = Pagume 6, 2015 ET (2015 is an Ethiopian leap year: 2015 % 4 === 3)
    '2015 pagume leap day' => [2023, 9, 11, ['year' => 2015, 'month' => 13, 'day' => 6]],
]);

test('formatGc renders the Amharic month name and Ethiopian year', function (): void {
    expect(EthiopianCalendar::formatGc(Carbon::create(2024, 9, 11), 'd M Y'))
        ->toBe('1 መስከረም 2017');
});

test('fromGregorian round-trips with the year-only format hint', function (): void {
    expect(EthiopianCalendar::formatGc(Carbon::create(2023, 9, 12), 'Y'))->toBe('2016');
});
