{{-- Sidebar Menu Component - Darker, Independently Scrollable --}}
<aside
    class="sidebar"
    style="display: none;"
    x-show="sidebarOpen"
    x-transition:enter="transition-transform duration-300"
    x-transition:enter-start="-translate-x-full lg:translate-x-0"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition-transform duration-300"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full lg:translate-x-0"
    :class="{ 'w-20': sidebarCollapsed, 'w-72': !sidebarCollapsed }"
>
    {{-- Logo Header --}}
    <div class="sidebar-header">
        <div class="flex min-w-0 items-center gap-3">
            <img
                class="h-8 flex-shrink-0"
                src="{{ theme_asset('images/logo-dark.svg') }}"
                alt="Logo"
            >
            <span
                class="truncate text-xl font-bold text-white"
                x-show="!sidebarCollapsed"
                x-transition
            >
                Member4VM
            </span>
        </div>

        {{-- Collapse Toggle (Desktop Only) --}}
        <button
            class="hidden flex-shrink-0 text-gray-400 transition-colors hover:text-white lg:block"
            @click="sidebarCollapsed = !sidebarCollapsed"
            x-show="!sidebarCollapsed"
            x-transition
        >
            <svg
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
                />
            </svg>
        </button>

        {{-- Expand Icon (when collapsed) --}}
        <button
            class="hidden text-gray-400 transition-colors hover:text-white lg:block"
            @click="sidebarCollapsed = !sidebarCollapsed"
            x-show="sidebarCollapsed"
            x-transition
        >
            <svg
                class="h-5 w-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 5l7 7-7 7M5 5l7 7-7 7"
                />
            </svg>
        </button>
    </div>

    {{-- Scrollable Menu Area --}}
    <nav class="sidebar-nav">
        <ul
            class="space-y-1"
            x-data="{ selected: '{{ synav()->getActiveMenu() }}' }"
        >
            @foreach (synav()->backend() as $key => $menu)
                @canAccess($menu['acl'] ?? null)
                <li @isset($menu['sub']) x-data="{ open: selected.startsWith('{{ $key }}') }" @endisset>
                    @isset($menu['sub'])
                        {{-- Menu item with submenu --}}
                        <button
                            class="menu-item w-full {{ synav()->is($key) ? 'menu-item-active' : '' }} group relative"
                            @click="open = !open; selected = selected === '{{ $key }}' ? '' : '{{ $key }}'"
                            :class="{ 'justify-center': sidebarCollapsed }"
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                @if (!empty($menu['icon']))
                                    <span class="{{ $menu['icon'] }} h-5 w-5 flex-shrink-0"></span>
                                @endif
                                <span
                                    class="font-medium"
                                    x-show="!sidebarCollapsed"
                                    x-transition
                                >
                                    {{ synav()->translate($menu['name']) }}
                                </span>
                            </div>
                            <svg
                                class="h-4 w-4 flex-shrink-0 transition-transform"
                                x-show="!sidebarCollapsed"
                                :class="{ 'rotate-180': open }"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd"
                                />
                            </svg>

                            {{-- Tooltip for collapsed sidebar --}}
                            <span
                                class="menu-tooltip"
                                x-show="sidebarCollapsed"
                                x-cloak
                            >
                                {{ synav()->translate($menu['name']) }}
                            </span>
                        </button>

                        {{-- Submenu (Level 2) --}}
                        <ul
                            class="submenu"
                            style="display: none;"
                            x-show="open && !sidebarCollapsed"
                            x-transition
                        >
                            @foreach ($menu['sub'] as $subKey => $subMenu)
                                @canAccess($subMenu['acl'] ?? null)
                                <li @isset($subMenu['sub']) x-data="{ openSub: selected.startsWith('{{ $key }}.{{ $subKey }}') }" @endisset>
                                    @isset($subMenu['sub'])
                                        {{-- Submenu item with sub-submenu --}}
                                        <button
                                            class="submenu-item w-full text-left {{ synav()->is($key . '.' . $subKey) ? 'submenu-item-active' : '' }}"
                                            @click="openSub = !openSub"
                                        >
                                            <div class="flex min-w-0 flex-1 items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    @if (!empty($subMenu['icon']))
                                                        <span class="{{ $subMenu['icon'] }}"></span>
                                                    @endif
                                                    <span>{{ synav()->translate($subMenu['name']) }}</span>
                                                </div>
                                                <svg
                                                    class="h-3 w-3 flex-shrink-0 transition-transform"
                                                    :class="{ 'rotate-180': openSub }"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </div>
                                        </button>

                                        {{-- Sub-submenu (Level 3) --}}
                                        <ul
                                            class="ml-4 mt-1 space-y-1"
                                            style="display: none;"
                                            x-show="openSub"
                                            x-transition
                                        >
                                            @foreach ($subMenu['sub'] as $subSubKey => $subSubMenu)
                                                @canAccess($subSubMenu['acl'] ?? null)
                                                <li>
                                                    <a
                                                        class="submenu-item block py-1.5 text-sm {{ synav()->is($key . '.' . $subKey . '.' . $subSubKey) ? 'submenu-item-active' : '' }}"
                                                        href="{{ backend_route($subSubMenu['route']) }}"
                                                        wire:navigate
                                                    >
                                                        @if (!empty($subSubMenu['icon']))
                                                            <span class="{{ $subSubMenu['icon'] }} mr-2"></span>
                                                        @endif
                                                        {{ synav()->translate($subSubMenu['name']) }}
                                                    </a>
                                                </li>
                                                @endcanAccess
                                            @endforeach
                                        </ul>
                                    @else
                                        {{-- Simple submenu item --}}
                                        <a
                                            class="submenu-item {{ synav()->is($key . '.' . $subKey) ? 'submenu-item-active' : '' }}"
                                            href="{{ backend_route($subMenu['route']) }}"
                                            wire:navigate
                                        >
                                            @if (!empty($subMenu['icon']))
                                                <span class="{{ $subMenu['icon'] }} mr-2"></span>
                                            @endif
                                            {{ synav()->translate($subMenu['name']) }}
                                        </a>
                                    @endisset
                                </li>
                                @endcanAccess
                            @endforeach
                        </ul>
                    @else
                        {{-- Simple menu item without submenu --}}
                        <a
                            class="menu-item {{ synav()->is($key) ? 'menu-item-active' : '' }} group relative"
                            href="{{ backend_route($menu['route']) }}"
                            :class="{ 'justify-center': sidebarCollapsed }"
                            wire:navigate
                        >
                            @if (!empty($menu['icon']))
                                <span class="{{ $menu['icon'] }} h-5 w-5 flex-shrink-0"></span>
                            @endif
                            <span
                                class="font-medium"
                                x-show="!sidebarCollapsed"
                                x-transition
                            >
                                {{ synav()->translate($menu['name']) }}
                            </span>

                            {{-- Tooltip for collapsed sidebar --}}
                            <span
                                class="menu-tooltip"
                                x-show="sidebarCollapsed"
                                x-cloak
                            >
                                {{ synav()->translate($menu['name']) }}
                            </span>
                        </a>
                    @endisset
                </li>
                @endcanAccess
            @endforeach
        </ul>
    </nav>

    {{-- User Profile (Bottom) --}}
    <div class="sidebar-footer">
        <div
            class="flex cursor-pointer items-center gap-3 rounded-lg p-3 transition-all hover:bg-white/5"
            :class="{ 'justify-center': sidebarCollapsed }"
        >
            <img
                class="h-10 w-10 flex-shrink-0 rounded-full"
                src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=3B82F6&color=fff"
                alt="User"
            >
            <div
                class="min-w-0 flex-1"
                x-show="!sidebarCollapsed"
                x-transition
            >
                <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name ?? 'User Name' }}</p>
                <p class="truncate text-xs text-gray-400">{{ auth()->user()->email ?? 'user@example.com' }}</p>
            </div>
        </div>
    </div>
</aside>
