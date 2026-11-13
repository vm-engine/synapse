{{--
    Breadcrumbs Component — page title + trail for backend pages.

    Props:
    - breadcrumbs (Breadcrumbs|null): VmEngine\Synapse\Services\Helper\Breadcrumbs

    Usage: <x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />
--}}
<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-center justify-between gap-3']) }}>
    <h2
        class="text-xl font-semibold text-gray-800 dark:text-white/90"
        x-text="$data.pageName || ''"
    ></h2>

    <nav aria-label="{{ __('Breadcrumb') }}">
        <ol class="flex items-center gap-1.5">
            <li>
                <a
                    class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                    href="{{ $homeUrl() }}"
                    wire:navigate
                >
                    {{ __('home::menu.be.index') }}
                    @if ($crumbs)
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    @endif
                </a>
            </li>
            @foreach ($crumbs as $crumb)
                <li class="text-sm text-gray-800 dark:text-white/90">
                    @if (! empty($crumb['icon']))
                        <span class="{{ $crumb['icon'] }}" aria-hidden="true"></span>
                    @endif
                    <a
                        class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                        href="{{ $crumb['url'] }}"
                        title="{{ $crumb['label'] }}"
                        wire:navigate
                        @if ($crumb['last']) aria-current="page" @endif
                    >
                        {{ $crumb['label'] }}
                        @unless ($crumb['last'])
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" aria-hidden="true">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        @endunless
                    </a>
                </li>
            @endforeach
        </ol>
    </nav>
</div>
