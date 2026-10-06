{{--
    Calendar Component (month view)

    Server-rendered month grid (md and up) + agenda list (mobile). The host
    Livewire component owns the visible month and the events.

    Props:
    - month (string): Visible month, Y-m (malformed → current month)
    - events (array): [['id' => .., 'title' => .., 'date' => 'Y-m-d', 'color' => 'gray|brand|success|warning|danger|info'], ...]
    - navMethod (string): Host method called as navMethod('Y-m') by prev/today/next
    - eventMethod (string): Host method called as eventMethod('id') when an event is clicked
    - dayMethod (string): Host method called as dayMethod('Y-m-d') by a day number / "+N more"
    - weekStart (string): 'monday' (default) or 'sunday'

    Usage:
    <x-synapse-calendar :month="$month" :events="$this->events" nav-method="setMonth" event-method="openEvent" day-method="createOn" />
--}}

<div
    class="syn-calendar"
    {{ $attributes }}
>
    <div class="syn-calendar-header">
        <h3 class="syn-calendar-title">{{ $monthLabel() }}</h3>

        @if ($navMethod)
            <div class="syn-calendar-nav">
                <button
                    class="btn-icon secondary"
                    type="button"
                    aria-label="{{ __('Previous month') }}"
                    wire:click="{{ $navMethod }}('{{ $previousMonth() }}')"
                ><i class="ph ph-caret-left"></i></button>
                <button
                    class="btn secondary"
                    type="button"
                    wire:click="{{ $navMethod }}('{{ $currentMonth() }}')"
                >{{ __('Today') }}</button>
                <button
                    class="btn-icon secondary"
                    type="button"
                    aria-label="{{ __('Next month') }}"
                    wire:click="{{ $navMethod }}('{{ $nextMonth() }}')"
                ><i class="ph ph-caret-right"></i></button>
            </div>
        @endif
    </div>

    <div
        class="syn-calendar-grid"
        role="grid"
    >
        @foreach ($weekdayLabels() as $weekday)
            <div
                class="syn-calendar-weekday"
                role="columnheader"
            >{{ $weekday }}</div>
        @endforeach

        @foreach ($weeks() as $week)
            @foreach ($week as $cell)
                <div
                    role="gridcell"
                    @class([
                        'syn-calendar-day',
                        'syn-calendar-day-outside' => !$cell['inMonth'],
                        'syn-calendar-day-today' => $cell['isToday'],
                    ])
                >
                    @if ($dayMethod)
                        <button
                            class="syn-calendar-day-number"
                            type="button"
                            wire:click="{{ $dayMethod }}('{{ $cell['date'] }}')"
                        >{{ $cell['day'] }}</button>
                    @else
                        <span class="syn-calendar-day-number">{{ $cell['day'] }}</span>
                    @endif

                    @foreach ($cell['events'] as $event)
                        @if ($eventMethod)
                            <button
                                class="syn-calendar-event syn-calendar-event-{{ $event['color'] }}"
                                type="button"
                                title="{{ $event['title'] }}"
                                wire:click="{{ $eventMethod }}('{{ $event['id'] }}')"
                            >{{ $event['title'] }}</button>
                        @else
                            <span
                                class="syn-calendar-event syn-calendar-event-{{ $event['color'] }}"
                                title="{{ $event['title'] }}"
                            >{{ $event['title'] }}</span>
                        @endif
                    @endforeach

                    @if ($cell['more'] > 0)
                        @if ($dayMethod)
                            <button
                                class="syn-calendar-more"
                                type="button"
                                wire:click="{{ $dayMethod }}('{{ $cell['date'] }}')"
                            >+{{ $cell['more'] }} {{ __('more') }}</button>
                        @else
                            <span class="syn-calendar-more">+{{ $cell['more'] }} {{ __('more') }}</span>
                        @endif
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>

    <ol class="syn-calendar-agenda">
        @forelse ($agenda() as $day)
            <li class="syn-calendar-agenda-day">
                <span class="syn-calendar-agenda-date">{{ $day['label'] }}</span>
                @foreach ($day['events'] as $event)
                    @if ($eventMethod)
                        <button
                            class="syn-calendar-event syn-calendar-event-{{ $event['color'] }}"
                            type="button"
                            wire:click="{{ $eventMethod }}('{{ $event['id'] }}')"
                        >{{ $event['title'] }}</button>
                    @else
                        <span
                            class="syn-calendar-event syn-calendar-event-{{ $event['color'] }}">{{ $event['title'] }}</span>
                    @endif
                @endforeach
            </li>
        @empty
            <li class="syn-calendar-agenda-empty">{{ __('No events this month') }}</li>
        @endforelse
    </ol>
</div>
