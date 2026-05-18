<div class="input-group">
    <span class="input-group-item left">{{ $currency }}</span>
    <input
        type="text"
        x-mask:dynamic="$money($input, ',', '.', 0)"
        x-on:focus="if ($event.target.value == '0') $event.target.select()"
        {{ $attributes->merge(['class' => 'form-input pl-[62px]!']) }}
    >
</div>
