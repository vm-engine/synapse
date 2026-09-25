<?php

declare(strict_types=1);

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Livewire;
use VmEngine\Synapse\Traits\RemembersQueryParams;

function createRemembersQueryParamsComponent(array $params = [], ?string $key = null, array $properties = []): object
{
    $component = new class
    {
        use RemembersQueryParams {
            mountRemembersQueryParams as public;
            dehydrateRemembersQueryParams as public;
            forgetRememberedParams as public;
            getRememberedParamValues as public;
            resolveCurrentUrlParams as public;
            restoredQuery as public;
            rememberedParams as protected traitRememberedParams;
            rememberKey as protected traitRememberKey;
        }

        /** @var array<int, string> */
        public array $_params = [];

        public ?string $_key = null;

        public string $search = '';

        public int $page = 1;

        public string $sortField = 'name';

        public ?string $filter = null;

        public bool $showTrashed = false;

        protected function rememberedParams(): array
        {
            return $this->_params;
        }

        protected function rememberKey(): string
        {
            return $this->_key ?? $this->traitRememberKey();
        }
    };

    $component->_params = $params;
    $component->_key = $key;

    foreach ($properties as $prop => $value) {
        if (property_exists($component, $prop)) {
            $component->{$prop} = $value;
        }
    }

    return $component;
}

// --- rememberedParams() ---

test('default rememberedParams returns empty array', function () {
    $component = new class
    {
        use RemembersQueryParams {
            rememberedParams as public traitRememberedParams;
        }
    };

    expect($component->traitRememberedParams())->toBe([]);
});

// --- rememberKey() ---

test('default rememberKey includes synapse:query_params: prefix', function () {
    $component = new class
    {
        use RemembersQueryParams {
            rememberKey as public traitRememberKey;
        }
    };

    $key = $component->traitRememberKey();

    expect($key)->toStartWith('synapse:query_params:');
    expect($key)->toContain(get_class($component));
});

test('custom rememberKey is used', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['search'],
        key: 'my.custom.key',
        properties: ['search' => 'custom-key-test'],
    );

    $component->dehydrateRemembersQueryParams();

    expect(session('my.custom.key'))->toBe(['search' => 'custom-key-test']);
});

// --- mountRemembersQueryParams() ---

test('restores session values on mount when no URL params', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['search', 'page'],
        key: 'test.restore',
    );

    session(['test.restore' => ['search' => 'hello', 'page' => 3]]);

    $component->mountRemembersQueryParams();

    expect($component->search)->toBe('hello');
    expect($component->page)->toBe(3);
});

test('does not restore when session is empty', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['search', 'page'],
        key: 'test.empty',
    );

    $component->mountRemembersQueryParams();

    expect($component->search)->toBe('');
    expect($component->page)->toBe(1);
});

test('does not restore when rememberedParams is empty', function () {
    $component = createRemembersQueryParamsComponent(
        params: [],
        key: 'test.no-params',
    );

    session(['test.no-params' => ['search' => 'should-not-apply']]);

    $component->mountRemembersQueryParams();

    expect($component->search)->toBe('');
});

test('partial restore only restores params that exist in session', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['search', 'page', 'sortField'],
        key: 'test.partial',
    );

    session(['test.partial' => ['search' => 'partial']]);

    $component->mountRemembersQueryParams();

    expect($component->search)->toBe('partial');
    expect($component->page)->toBe(1);
    expect($component->sortField)->toBe('name');
});

test('ignores non-existent properties', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['search', 'nonExistentProp'],
        key: 'test.nonexistent',
    );

    session(['test.nonexistent' => ['search' => 'valid', 'nonExistentProp' => 'ignored']]);

    $component->mountRemembersQueryParams();

    expect($component->search)->toBe('valid');
});

test('handles null values in session', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['filter'],
        key: 'test.null',
    );

    session(['test.null' => ['filter' => null]]);

    $component->mountRemembersQueryParams();

    expect($component->filter)->toBeNull();
});

test('handles empty string values in session', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['search'],
        key: 'test.empty-string',
    );

    $component->search = 'existing';
    session(['test.empty-string' => ['search' => '']]);

    $component->mountRemembersQueryParams();

    expect($component->search)->toBe('');
});

// --- dehydrateRemembersQueryParams() ---

test('saves to session on dehydrate', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['search', 'page'],
        key: 'test.dehydrate',
        properties: ['search' => 'saved-search', 'page' => 5],
    );

    $component->dehydrateRemembersQueryParams();

    expect(session('test.dehydrate'))->toBe([
        'search' => 'saved-search',
        'page' => 5,
    ]);
});

test('overwrites previous session values on dehydrate', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['search'],
        key: 'test.overwrite',
        properties: ['search' => 'first'],
    );

    $component->dehydrateRemembersQueryParams();
    expect(session('test.overwrite'))->toBe(['search' => 'first']);

    $component->search = 'second';
    $component->dehydrateRemembersQueryParams();
    expect(session('test.overwrite'))->toBe(['search' => 'second']);
});

test('does not write to session when rememberedParams is empty', function () {
    $component = createRemembersQueryParamsComponent(
        params: [],
        key: 'test.no-write',
    );

    $component->dehydrateRemembersQueryParams();

    expect(session()->has('test.no-write'))->toBeFalse();
});

test('only saves properties that exist on the component', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['search', 'missingProp'],
        key: 'test.existing-only',
        properties: ['search' => 'exists'],
    );

    $component->dehydrateRemembersQueryParams();

    expect(session('test.existing-only'))->toBe(['search' => 'exists']);
});

// --- forgetRememberedParams() ---

test('forgetRememberedParams clears session', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['search'],
        key: 'test.forget',
        properties: ['search' => 'to-forget'],
    );

    $component->dehydrateRemembersQueryParams();
    expect(session()->has('test.forget'))->toBeTrue();

    $component->forgetRememberedParams();
    expect(session()->has('test.forget'))->toBeFalse();
});

// --- getRememberedParamValues() ---

test('getRememberedParamValues returns stored data', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['search', 'page'],
        key: 'test.get-values',
    );

    session(['test.get-values' => ['search' => 'stored', 'page' => 7]]);

    $values = $component->getRememberedParamValues();

    expect($values)->toBe(['search' => 'stored', 'page' => 7]);
});

test('getRememberedParamValues returns empty array when no session data', function () {
    $component = createRemembersQueryParamsComponent(
        params: ['search'],
        key: 'test.get-empty',
    );

    expect($component->getRememberedParamValues())->toBe([]);
});

// --- resolveCurrentUrlParams() ---

test('resolveCurrentUrlParams returns empty when no query string', function () {
    $component = createRemembersQueryParamsComponent(params: ['search', 'page']);

    $result = $component->resolveCurrentUrlParams(['search', 'page']);

    expect($result)->toBe([]);
});

test('resolveCurrentUrlParams detects params from query string', function () {
    // Simulate request with query string
    request()->server->set('QUERY_STRING', 'search=hello&page=2');

    $component = createRemembersQueryParamsComponent(params: ['search', 'page', 'sortField']);

    $result = $component->resolveCurrentUrlParams(['search', 'page', 'sortField']);

    expect($result)->toBe(['search', 'page']);

    // Clean up
    request()->server->set('QUERY_STRING', '');
});

// --- Integration: mount + dehydrate cycle ---

test('full cycle: dehydrate saves, mount restores', function () {
    $key = 'test.full-cycle';

    // First component saves state
    $saver = createRemembersQueryParamsComponent(
        params: ['search', 'page'],
        key: $key,
        properties: ['search' => 'persisted', 'page' => 42],
    );
    $saver->dehydrateRemembersQueryParams();

    // Second component restores state
    $restorer = createRemembersQueryParamsComponent(
        params: ['search', 'page'],
        key: $key,
    );
    $restorer->mountRemembersQueryParams();

    expect($restorer->search)->toBe('persisted');
    expect($restorer->page)->toBe(42);
});

test('URL params take precedence over session values', function () {
    $key = 'test.url-wins';

    session([$key => ['search' => 'from-session', 'page' => 5]]);

    request()->server->set('QUERY_STRING', 'search=from-url');

    $component = createRemembersQueryParamsComponent(
        params: ['search', 'page'],
        key: $key,
        properties: ['search' => 'from-url'],
    );

    $component->mountRemembersQueryParams();

    // search should NOT be overwritten (URL wins)
    expect($component->search)->toBe('from-url');
    // page should be restored from session (not in URL)
    expect($component->page)->toBe(5);

    // Clean up
    request()->server->set('QUERY_STRING', '');
});

// --- Reflecting session-restored values in the URL ---

test('restoredQuery encodes bools as true/false like Livewire does', function () {
    $component = createRemembersQueryParamsComponent(params: ['search', 'showTrashed']);

    expect($component->restoredQuery(['search' => 'ada', 'showTrashed' => true]))
        ->toBe(['search' => 'ada', 'showTrashed' => 'true']);
});

test('restoredQuery flattens arrays into the key[i] form Livewire reads back as arrays', function () {
    $component = createRemembersQueryParamsComponent(params: ['search']);

    expect($component->restoredQuery(['filterIds' => [3, 7], 'filterMap' => ['a' => ['b' => true]]]))
        ->toBe(['filterIds[0]' => 3, 'filterIds[1]' => 7, 'filterMap[a][b]' => 'true']);
});

test('restoredQuery is empty when nothing was restored', function () {
    $component = createRemembersQueryParamsComponent(params: ['search']);

    expect($component->restoredQuery([]))->toBe([]);
});

test('restoredQuery skips null values and uses the #[Url(as:)] alias', function () {
    $component = new class
    {
        use RemembersQueryParams {
            restoredQuery as public;
        }

        #[Url(as: 'q')]
        public string $search = '';

        public ?string $filter = null;
    };

    expect($component->restoredQuery(['search' => 'x', 'filter' => null]))->toBe(['q' => 'x']);
});

test('resolveCurrentUrlParams recognises a param by its #[Url(as:)] alias', function () {
    request()->server->set('QUERY_STRING', 'q=hello');

    $component = new class
    {
        use RemembersQueryParams {
            resolveCurrentUrlParams as public;
        }

        #[Url(as: 'q')]
        public string $search = '';
    };

    expect($component->resolveCurrentUrlParams(['search']))->toBe(['search']);

    request()->server->set('QUERY_STRING', '');
});

test('a Livewire component dispatches session-restored values so the URL can show them', function () {
    session(['test.redirect-restore' => ['showTrashed' => true, 'search' => '']]);

    Livewire::test(RemembersQueryParamsRedirectComponent::class)
        ->assertSet('showTrashed', true)
        ->assertNoRedirect()
        ->assertDispatched('synapse-restored-query', query: ['showTrashed' => 'true']);
});

test('a Livewire component dispatches nothing when restored values equal the defaults', function () {
    session(['test.redirect-restore' => ['showTrashed' => false, 'search' => '']]);

    Livewire::test(RemembersQueryParamsRedirectComponent::class)
        ->assertNotDispatched('synapse-restored-query');
});
class RemembersQueryParamsRedirectComponent extends Component
{
    use RemembersQueryParams;

    #[Url]
    public string $search = '';

    #[Url]
    public bool $showTrashed = false;

    protected function rememberedParams(): array
    {
        return ['search', 'showTrashed'];
    }

    protected function rememberKey(): string
    {
        return 'test.redirect-restore';
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
