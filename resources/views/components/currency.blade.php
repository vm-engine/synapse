<div class="input-group">
    <span class="input-group-item left">{{ $currency }}</span>
    <input type="text" class="form-input pl-[62px]!" x-mask:dynamic="$money($input, ',', '.', 0)" {{ $attributes }}>
</div>
