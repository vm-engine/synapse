{{--
    Steps Component

    Server-driven step indicator for wizards (modal or full page). The host
    Livewire component owns the current step; completed steps can call back.

    Props:
    - steps (array): Step labels in order
    - current (int): 1-based active step (clamped to the valid range)
    - goMethod (string): Optional host method, called as goMethod(stepNumber) from completed steps

    Usage:
    <x-synapse-steps :steps="['Details', 'Media', 'Review']" :current="$step" go-method="goToStep" />
--}}

<ol
    class="syn-steps"
    {{ $attributes }}
>
    @foreach ($items() as $item)
        <li
            class="syn-step syn-step-{{ $item['state'] }}"
            @if ($item['state'] === 'current') aria-current="step" @endif
        >
            @if ($item['clickable'])
                <button
                    class="syn-step-trigger"
                    type="button"
                    wire:click="{{ $goMethod }}({{ $item['number'] }})"
                >
                    <span class="syn-step-marker"><i class="ph ph-check"></i></span>
                    <span class="syn-step-label">{{ $item['label'] }}</span>
                </button>
            @else
                <div class="syn-step-trigger">
                    <span class="syn-step-marker">
                        @if ($item['state'] === 'complete')
                            <i class="ph ph-check"></i>
                        @else
                            {{ $item['number'] }}
                        @endif
                    </span>
                    <span class="syn-step-label">{{ $item['label'] }}</span>
                </div>
            @endif
        </li>
    @endforeach
</ol>
