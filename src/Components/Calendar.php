<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Carbon\CarbonImmutable;
use Illuminate\View\Component;
use VmEngine\Synapse\Components\Concerns\SanitizesWireArguments;

/**
 * @phpstan-type CalendarEvent array{id: string, title: string, color: string}
 * @phpstan-type CalendarCell array{date: string, day: int, inMonth: bool, isToday: bool, events: list<CalendarEvent>, more: int}
 */
class Calendar extends Component
{
    use SanitizesWireArguments;

    /** @var array<int, string> */
    private const COLORS = ['gray', 'brand', 'success', 'warning', 'danger', 'info'];

    private const MAX_EVENTS_PER_DAY = 3;

    public CarbonImmutable $monthStart;

    public string $weekStart;

    public ?string $navMethod;

    public ?string $eventMethod;

    public ?string $dayMethod;

    /** @var array<string, list<CalendarEvent>> */
    private array $eventsByDate;

    /**
     * @param  string|null  $month  Visible month as Y-m (malformed → current month)
     * @param  array<int, mixed>  $events  [['id' => int|string, 'title' => string, 'date' => 'Y-m-d', 'color' => ?string], ...]
     * @param  string|null  $navMethod  Host method called as navMethod('Y-m')
     * @param  string|null  $eventMethod  Host method called as eventMethod('id')
     * @param  string|null  $dayMethod  Host method called as dayMethod('Y-m-d')
     * @param  string  $weekStart  'monday' (default) or 'sunday'
     */
    public function __construct(
        ?string $month = null,
        array $events = [],
        ?string $navMethod = null,
        ?string $eventMethod = null,
        ?string $dayMethod = null,
        string $weekStart = 'monday',
    ) {
        $this->monthStart = $this->parseMonth($month);
        $this->weekStart = $weekStart === 'sunday' ? 'sunday' : 'monday';
        $this->navMethod = $this->wireMethod($navMethod);
        $this->eventMethod = $this->wireMethod($eventMethod);
        $this->dayMethod = $this->wireMethod($dayMethod);
        $this->eventsByDate = $this->groupEvents($events);
    }

    /**
     * @return list<list<CalendarCell>>
     */
    public function weeks(): array
    {
        // Carbon day-of-week numbers: 0 = Sunday, 1 = Monday, 6 = Saturday.
        $firstDay = $this->weekStart === 'sunday' ? 0 : 1;
        $lastDay = $this->weekStart === 'sunday' ? 6 : 0;

        $cursor = $this->monthStart->startOfWeek($firstDay);
        $last = $this->monthStart->endOfMonth()->endOfWeek($lastDay)->startOfDay();
        $today = CarbonImmutable::today()->toDateString();

        $weeks = [];
        $week = [];

        while ($cursor->lte($last)) {
            $date = $cursor->toDateString();
            $events = $this->eventsByDate[$date] ?? [];

            $week[] = [
                'date' => $date,
                'day' => $cursor->day,
                'inMonth' => $cursor->month === $this->monthStart->month,
                'isToday' => $date === $today,
                'events' => array_slice($events, 0, self::MAX_EVENTS_PER_DAY),
                'more' => max(0, count($events) - self::MAX_EVENTS_PER_DAY),
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }

            $cursor = $cursor->addDay();
        }

        return $weeks;
    }

    /**
     * In-month days that have events, in date order (mobile agenda view).
     *
     * @return list<array{date: string, label: string, events: list<CalendarEvent>}>
     */
    public function agenda(): array
    {
        $prefix = $this->monthStart->format('Y-m-');
        $dates = array_filter(
            array_keys($this->eventsByDate),
            fn (string $date): bool => str_starts_with($date, $prefix),
        );
        sort($dates);

        return array_map(fn (string $date): array => [
            'date' => $date,
            'label' => CarbonImmutable::parse($date)->isoFormat('ddd, D MMM'),
            'events' => $this->eventsByDate[$date],
        ], $dates);
    }

    /**
     * @return list<string>
     */
    public function weekdayLabels(): array
    {
        return array_map(
            fn (array $cell): string => CarbonImmutable::parse($cell['date'])->isoFormat('ddd'),
            $this->weeks()[0],
        );
    }

    public function monthLabel(): string
    {
        return $this->monthStart->isoFormat('MMMM YYYY');
    }

    public function previousMonth(): string
    {
        return $this->monthStart->subMonthNoOverflow()->format('Y-m');
    }

    public function nextMonth(): string
    {
        return $this->monthStart->addMonthNoOverflow()->format('Y-m');
    }

    public function currentMonth(): string
    {
        return CarbonImmutable::now()->format('Y-m');
    }

    public function render()
    {
        return view('synapse::components.calendar');
    }

    private function parseMonth(?string $month): CarbonImmutable
    {
        if ($month !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1) {
            $parsed = CarbonImmutable::createFromFormat('!Y-m', $month);

            if ($parsed instanceof CarbonImmutable) {
                return $parsed->startOfMonth();
            }
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    /**
     * @param  array<int, mixed>  $events
     * @return array<string, list<CalendarEvent>>
     */
    private function groupEvents(array $events): array
    {
        $grouped = [];

        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }

            $id = isset($event['id']) && (is_int($event['id']) || is_string($event['id']))
                ? $this->safeKey($event['id'])
                : null;
            $title = $event['title'] ?? null;
            $date = $event['date'] ?? null;

            if ($id === null || ! is_string($title) || $title === '' || ! is_string($date) || ! $this->isValidDate($date)) {
                continue;
            }

            $color = $event['color'] ?? null;

            $grouped[$date][] = [
                'id' => $id,
                'title' => $title,
                'color' => is_string($color) && in_array($color, self::COLORS, true) ? $color : 'brand',
            ];
        }

        return $grouped;
    }

    private function isValidDate(string $date): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) !== 1) {
            return false;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }
}
