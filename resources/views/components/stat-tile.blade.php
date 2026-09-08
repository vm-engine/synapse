{{--
    Stat Tile Component

    A single metric card. Two variants:

    - variant="tile" (default): icon square on the left, label/value stacked
      on the right. Covers both the icon-card dashboard style and the plain
      label/value "quick stats" style (:bordered="false", no icon) seen in
      synmod-pm's project-detail view.
    - variant="card": title row up top (optionally with an inline icon), a
      big value below, an optional sublabel caption, optional whole-card
      color tint, and optional clickable/hover-shadow affordance.

    Props:
    - icon (string): Optional FontAwesome icon class, e.g. 'fa-solid fa-file-invoice'
    - color (string): Accent color — 'gray', 'blue', 'indigo', 'purple', 'red', 'orange', 'green' (default: 'gray')
    - label (string): Caption. Can be overridden with <x-slot:label>.
    - value (string): Already-formatted value (caller formats numbers/currency). Can be overridden with <x-slot:value>.
    - valueColor (string): Value text color override, same palette as `color` (tile variant only)
    - bordered (bool): tile variant only — white card + border + rounded-xl (default) vs gray-50 flat + rounded-lg (false)
    - variant (string): 'tile' (default) or 'card'
    - iconPosition (string): card variant only — 'left' (icon square beside the stacked
      title/value/sublabel), 'title' (bare/circle icon inline at the end of the title row,
      default), or 'background' (large, low-opacity, cropped icon in the bottom-right corner)
    - iconBackground (bool): both variants — show the colored square/circle behind the icon
      (default true) or a bare colored icon glyph (false). Ignored for iconPosition="background".
    - tinted (bool): card variant only — wash the whole card in `color` (bg/border/title/value/icon).
      Default false = plain white card with a muted icon regardless of `color`.
    - sublabel (string): card variant only — small caption line under the value. Can be
      overridden with <x-slot:sublabel>.
    - sublabelColor (string): sublabel text color override, same palette as `color`
    - href (string): card variant only — wraps the tile in an <a>, implies clickable
    - clickable (bool): card variant only — hover-shadow/cursor-pointer affordance without
      requiring `href` (e.g. for a wire:click card)

    Slots:
    - default: tile variant — optional extra content below the value (a badge, a progress bar).
      card variant — optional extra content below the sublabel.
    - label / value / sublabel: override the matching prop with rich content.

    Usage Examples:

    <x-synapse-stat-tile icon="fa-solid fa-file-invoice" color="blue" label="Total Invoices" value="2.613" />

    <x-synapse-stat-tile
        variant="card" icon="fa-solid fa-hourglass-half" color="red" tinted
        label="Stuck Transactions" value="4,277" sublabel="> 30 min old"
    />

    <x-synapse-stat-tile
        variant="card" clickable href="{{ route('...') }}"
        label="Transactions Today" value="0" sublabel="+0% vs yesterday (0)" sublabelColor="green"
    />
--}}

@if ($variant === 'card')
    @php
        $tag = $href ? 'a' : 'div';
        $isClickable = $clickable || $href;
        $iconClass =
            'syn-stat-tile-icon syn-stat-tile-icon-' . $color . ($iconBackground ? '' : ' syn-stat-tile-icon-bare');
    @endphp
    <{{ $tag }}
        @if ($href) href="{{ $href }}" @endif
        {{ $attributes->class([
            'syn-stat-tile-card',
            'syn-stat-tile-card-tinted syn-stat-tile-card-tinted-' . $color => $tinted,
            'syn-stat-tile-card-clickable' => $isClickable,
        ]) }}
    >
        @if ($iconPosition === 'left' && $icon)
            <div class="syn-stat-tile-card-row">
                <div class="{{ $iconClass }}"><i class="{{ $icon }}"></i></div>
                <div class="syn-stat-tile-card-body">
                    @if ($label)
                        <div class="syn-stat-tile-card-title">{{ $label }}</div>
                    @endif
                    @if ($value)
                        <div class="syn-stat-tile-card-value">{{ $value }}</div>
                    @endif
                    @if ($sublabel)
                        <div
                            class="syn-stat-tile-card-sublabel {{ $sublabelColor ? 'syn-stat-tile-value-' . $sublabelColor : '' }}">
                            {{ $sublabel }}
                        </div>
                    @endif
                    {{ $slot }}
                </div>
            </div>
        @else
            @if ($iconPosition === 'background' && $icon)
                <i
                    class="{{ $icon }} syn-stat-tile-card-icon-bg {{ $color !== 'gray' ? 'syn-stat-tile-value-' . $color : '' }}"></i>
            @endif

            <div class="syn-stat-tile-card-header">
                @if ($label)
                    <div class="syn-stat-tile-card-title">{{ $label }}</div>
                @endif
                @if ($iconPosition === 'title' && $icon)
                    <span
                        class="syn-stat-tile-card-icon-title syn-stat-tile-icon-{{ $color }} {{ $iconBackground ? '' : 'syn-stat-tile-icon-bare' }}"
                    >
                        <i class="{{ $icon }}"></i>
                    </span>
                @endif
            </div>

            @if ($value)
                <div class="syn-stat-tile-card-value">{{ $value }}</div>
            @endif
            @if ($sublabel)
                <div
                    class="syn-stat-tile-card-sublabel {{ $sublabelColor ? 'syn-stat-tile-value-' . $sublabelColor : '' }}">
                    {{ $sublabel }}
                </div>
            @endif

            {{ $slot }}
        @endif
        </{{ $tag }}>
    @else
        <div class="syn-stat-tile {{ $bordered ? 'syn-stat-tile-bordered' : 'syn-stat-tile-flat' }}">
            @if ($icon)
                <div
                    class="syn-stat-tile-icon syn-stat-tile-icon-{{ $color }} {{ $iconBackground ? '' : 'syn-stat-tile-icon-bare' }}">
                    <i class="{{ $icon }}"></i>
                </div>
            @endif

            <div class="syn-stat-tile-body">
                @if ($label)
                    <div class="syn-stat-tile-label">{{ $label }}</div>
                @endif

                @if ($value)
                    <div class="syn-stat-tile-value {{ $valueColor ? 'syn-stat-tile-value-' . $valueColor : '' }}">
                        {{ $value }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>
@endif
{{-- ponytail: clickable (non-href) cards and Panel's collapsible header are click-only,
     no keyboard/tabindex/role support. Add if these need to be keyboard-operable. --}}
