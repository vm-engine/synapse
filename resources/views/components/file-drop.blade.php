@once
    <style>
        .synapse-file-drop {
            position: relative;
            width: 100%;
        }

        .synapse-file-drop__zone {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 28px 20px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #fafbfc;
            color: #475569;
            cursor: pointer;
            text-align: center;
            transition:
                border-color 150ms ease,
                background 150ms ease,
                color 150ms ease,
                box-shadow 150ms ease,
                transform 150ms ease;
        }

        .synapse-file-drop__zone:hover {
            border-color: #00a2b9;
            background: #e7f8fb;
            color: #006b7a;
            box-shadow: 0 4px 12px rgba(0, 162, 185, 0.12);
            transform: translateY(-1px);
        }

        .synapse-file-drop__zone.is-dragging {
            border-color: #00a2b9;
            border-style: solid;
            background: #cdeff4;
            color: #006b7a;
            box-shadow: 0 0 0 4px rgba(0, 162, 185, 0.18);
        }

        .synapse-file-drop__zone:focus-within {
            outline: none;
            border-color: #00a2b9;
            box-shadow: 0 0 0 3px rgba(0, 162, 185, 0.2);
        }

        .synapse-file-drop__zone:active {
            transform: translateY(0);
        }

        .synapse-file-drop__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 999px;
            background: rgba(0, 162, 185, 0.1);
            color: #00a2b9;
            font-size: 1.125rem;
            transition:
                background 150ms ease,
                transform 150ms ease;
        }

        .synapse-file-drop__zone:hover .synapse-file-drop__icon {
            background: rgba(0, 162, 185, 0.18);
            transform: scale(1.05);
        }

        .synapse-file-drop__primary {
            display: block;
            font-size: 0.9375rem;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.4;
        }

        .synapse-file-drop__zone:hover .synapse-file-drop__primary {
            color: #003a43;
        }

        .synapse-file-drop__primary em {
            font-style: normal;
            color: #00a2b9;
            text-decoration: underline;
            text-decoration-thickness: 1px;
            text-underline-offset: 3px;
        }

        .synapse-file-drop__hint {
            display: block;
            font-size: 0.75rem;
            color: #64748b;
            line-height: 1.4;
        }

        .synapse-file-drop__counter {
            display: inline-flex;
            align-items: center;
            margin-top: 2px;
            padding: 2px 10px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .synapse-file-drop__zone:hover .synapse-file-drop__counter {
            border-color: #00a2b9;
            color: #006b7a;
        }

        .synapse-file-drop__input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            font-size: 0;
            color: transparent;
        }

        .synapse-file-drop__input::file-selector-button,
        .synapse-file-drop__input::-webkit-file-upload-button {
            display: none;
        }

        .synapse-file-drop__full {
            margin: 0;
            padding: 16px;
            font-size: 0.8125rem;
            color: #64748b;
            text-align: center;
            background: #f1f5f9;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
        }

        .synapse-file-drop__error {
            margin: 6px 0 0;
            font-size: 0.75rem;
            color: #be123c;
        }

        .synapse-file-drop__uploading {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            font-size: 0.75rem;
            color: #006b7a;
            font-weight: 500;
        }

        .synapse-file-drop__uploading::before {
            content: "";
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 999px;
            border: 2px solid #00a2b9;
            border-top-color: transparent;
            animation: synapse-file-drop-spin 0.7s linear infinite;
        }

        @keyframes synapse-file-drop-spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
@endonce

<div
    class="synapse-file-drop"
    x-data="{
        isDragging: false,
        handleDrop(e) {
            this.isDragging = false;
            const input = this.$refs.input;
            if (!input) return;
            const files = e.dataTransfer.files;
            if (!files || files.length === 0) return;
            input.files = files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }"
>
    @if ($isFull())
        <p class="synapse-file-drop__full">{{ $resolvedFullText() }}</p>
    @else
        <label
            class="synapse-file-drop__zone"
            :class="{ 'is-dragging': isDragging }"
            @dragover.prevent="isDragging = true"
            @dragenter.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop($event)"
        >
            <span
                class="synapse-file-drop__icon"
                aria-hidden="true"
            >
                <i class="fa-solid fa-cloud-arrow-up"></i>
            </span>
            <span class="synapse-file-drop__primary">{!! $resolvedLabel() !!}</span>
            @if ($resolvedHint())
                <span class="synapse-file-drop__hint">{{ $resolvedHint() }}</span>
            @endif
            @if ($counterText())
                <span class="synapse-file-drop__counter">{{ $counterText() }}</span>
            @endif
            <input
                class="synapse-file-drop__input"
                type="file"
                name="{{ $name }}"
                x-ref="input"
                @if ($wireModel) wire:model="{{ $wireModel }}" @endif
                @if ($acceptString()) accept="{{ $acceptString() }}" @endif
                @if ($multiple) multiple @endif
                {{ $attributes->except(['class']) }}
            >
        </label>
    @endif

    @if ($error)
        <p class="synapse-file-drop__error">{{ $error }}</p>
    @endif

    @if ($wireModel)
        <div
            class="synapse-file-drop__uploading"
            wire:loading
            wire:target="{{ $wireModel }}"
        >
            {{ $resolvedUploadingText() }}
        </div>
    @endif
</div>
