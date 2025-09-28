<div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
    <select class="form-input" :class="isOptionSelected && 'text-gray-800 dark:text-white/90'"
        @change="isOptionSelected = true" {{ $attributes }}>
        @foreach($options as $key => $option)
        <option value="{{ $key }}" wire:key="{{ $key }}">{{ $option }}</option>
        @endforeach
    </select>
    <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
        <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>
    </span>
</div>
