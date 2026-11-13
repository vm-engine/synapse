{{--
    Section Heading Component — left-hand title of a two-column form section.

    Props: title (string), help (string, optional)

    Usage: <x-synapse-section-heading :title="__('crm::labels.contact')" :help="__('crm::labels.contact_help')" />
--}}
<div {{ $attributes }}>
    <p class="syn-section-label">{{ $title }}</p>
    @if ($help)
        <p class="form-help mt-1">{{ $help }}</p>
    @endif
</div>
