<div class="input-group">
    <span class="input-group-item left">{{ $currency }}</span>
    <input
        class="form-input pl-[62px]!"
        type="text"
        x-mask:dynamic="$money($input, ',', '.', 0)"
        {{ $attributes }}
    >
</div>
