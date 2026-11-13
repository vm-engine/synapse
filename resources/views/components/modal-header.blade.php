{{--
    Modal Header Component — for a form rendered inside <x-synapse-modal>.

    Props:
    - modal (string): the modal's name
    - title (string): heading; its id is modal-{name}-title, so set labelledby="modal-{name}-title" on the modal

    Usage: <x-synapse-modal-header modal="tax-rate-form" :title="$title" />
--}}
<div {{ $attributes->class(['syn-modal-header']) }}>
    <h3 class="syn-modal-title" id="modal-{{ $name }}-title">{{ $title }}</h3>
    <button class="syn-modal-close" type="button" @click="$dispatch('close-modal-{{ $name }}')">
        <span class="sr-only">{{ __('Close') }}</span><span class="ph ph-x text-xl" aria-hidden="true"></span>
    </button>
</div>
