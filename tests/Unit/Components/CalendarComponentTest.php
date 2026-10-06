<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\Calendar;

describe('Calendar Component', function () {
    beforeEach(function () {
        CarbonImmutable::setTestNow('2026-10-06 10:00:00');
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Calendar::class, 'synapse-calendar');
    });

    afterEach(function () {
        CarbonImmutable::setTestNow();
    });

    it('builds whole Monday-first weeks around the month', function () {
        $weeks = (new Calendar(month: '2026-10'))->weeks();

        expect($weeks)->toHaveCount(5)
            ->and($weeks[0][0]['date'])->toBe('2026-09-28')
            ->and($weeks[4][6]['date'])->toBe('2026-11-01');

        foreach ($weeks as $week) {
            expect($week)->toHaveCount(7);
        }
    });

    it('builds whole Sunday-first weeks when asked', function () {
        $weeks = (new Calendar(month: '2026-10', weekStart: 'sunday'))->weeks();

        expect($weeks[0][0]['date'])->toBe('2026-09-27')
            ->and($weeks[array_key_last($weeks)][6]['date'])->toBe('2026-10-31');
    });

    it('flags days outside the month and today', function () {
        $weeks = (new Calendar(month: '2026-10'))->weeks();

        expect($weeks[0][0]['inMonth'])->toBeFalse()
            ->and($weeks[0][3]['inMonth'])->toBeTrue()
            ->and($weeks[1][1]['date'])->toBe('2026-10-06')
            ->and($weeks[1][1]['isToday'])->toBeTrue()
            ->and($weeks[1][0]['isToday'])->toBeFalse();
    });

    it('shows at most three events per day and counts the rest', function () {
        $events = array_map(
            fn (int $i): array => ['id' => $i, 'title' => "Event {$i}", 'date' => '2026-10-06'],
            range(1, 5),
        );

        $cell = (new Calendar(month: '2026-10', events: $events))->weeks()[1][1];

        expect($cell['events'])->toHaveCount(3)
            ->and($cell['more'])->toBe(2)
            ->and($cell['events'][0])->toBe(['id' => '1', 'title' => 'Event 1', 'color' => 'brand']);
    });

    it('drops malformed events and normalizes unknown colors', function () {
        $calendar = new Calendar(month: '2026-10', events: [
            'not-an-array',
            ['id' => 1, 'date' => '2026-10-02'],
            ['id' => 2, 'title' => 'Bad date', 'date' => '2026-13-40'],
            ['id' => 'a b', 'title' => 'Bad id', 'date' => '2026-10-02'],
            ['id' => 3, 'title' => 'Good', 'date' => '2026-10-02', 'color' => 'pink'],
            ['id' => 4, 'title' => 'Warn', 'date' => '2026-10-02', 'color' => 'warning'],
        ]);

        $cell = $calendar->weeks()[0][4];

        expect($cell['date'])->toBe('2026-10-02')
            ->and(array_column($cell['events'], 'id'))->toBe(['3', '4'])
            ->and(array_column($cell['events'], 'color'))->toBe(['brand', 'warning']);
    });

    it('falls back to the current month for a malformed month', function () {
        expect((new Calendar(month: 'October'))->monthStart->toDateString())->toBe('2026-10-01')
            ->and((new Calendar(month: '2026-13'))->monthStart->toDateString())->toBe('2026-10-01')
            ->and((new Calendar)->monthStart->toDateString())->toBe('2026-10-01');
    });

    it('navigates across year boundaries', function () {
        expect((new Calendar(month: '2026-12'))->nextMonth())->toBe('2027-01')
            ->and((new Calendar(month: '2026-01'))->previousMonth())->toBe('2025-12')
            ->and((new Calendar(month: '2026-03'))->currentMonth())->toBe('2026-10');
    });

    it('lists only in-month days with events in the agenda', function () {
        $agenda = (new Calendar(month: '2026-10', events: [
            ['id' => 1, 'title' => 'Late', 'date' => '2026-10-20'],
            ['id' => 2, 'title' => 'Early', 'date' => '2026-10-02'],
            ['id' => 3, 'title' => 'Next month', 'date' => '2026-11-01'],
        ]))->agenda();

        expect(array_column($agenda, 'date'))->toBe(['2026-10-02', '2026-10-20']);
    });

    it('renders navigation, event and day callbacks with escaped titles', function () {
        $html = Blade::render(
            '<x-synapse-calendar month="2026-10" :events="$events" nav-method="setMonth" event-method="openEvent" day-method="createOn" />',
            ['events' => [
                ['id' => 7, 'title' => '<b>Launch</b>', 'date' => '2026-10-09'],
                ['id' => 8, 'title' => 'A', 'date' => '2026-10-06'],
                ['id' => 9, 'title' => 'B', 'date' => '2026-10-06'],
                ['id' => 10, 'title' => 'C', 'date' => '2026-10-06'],
                ['id' => 11, 'title' => 'D', 'date' => '2026-10-06'],
            ]],
        );

        expect($html)->toContain('October 2026')
            ->toContain("wire:click=\"setMonth('2026-09')\"")
            ->toContain("wire:click=\"setMonth('2026-11')\"")
            ->toContain("wire:click=\"openEvent('7')\"")
            ->toContain("wire:click=\"createOn('2026-10-06')\"")
            ->toContain('+1')
            ->toContain('&lt;b&gt;Launch&lt;/b&gt;')
            ->toContain('syn-calendar-day-today');
    });

    it('drops unsafe callback method names', function () {
        $calendar = new Calendar(navMethod: 'x()', eventMethod: 'ok', dayMethod: '');

        expect($calendar->navMethod)->toBeNull()
            ->and($calendar->eventMethod)->toBe('ok')
            ->and($calendar->dayMethod)->toBeNull();
    });
});
