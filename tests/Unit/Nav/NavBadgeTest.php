<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use VmEngine\Synapse\Nav\NavBadge;
use VmEngine\Synapse\Nav\NavBadgeRepository;

class CountingBadge extends NavBadge
{
    public static int $value = 3;

    public static int $calls = 0;

    public function count(): ?int
    {
        self::$calls++;

        return self::$value;
    }
}

class UncachedBadge extends CountingBadge
{
    public function ttl(): ?int
    {
        return 0;
    }
}

class ForeverBadge extends CountingBadge
{
    public function ttl(): ?int
    {
        return null;
    }
}

class ScopedBadge extends NavBadge
{
    public static string $viewer = 'a';

    /** @var array<string, int> */
    public static array $values = ['a' => 1, 'b' => 5];

    public function count(): ?int
    {
        return self::$values[self::$viewer];
    }

    public function scope(): string
    {
        return self::$viewer;
    }
}

class BadgeSourceModel extends Model {}

class BadgeSourceEvent {}

class ListeningBadge extends CountingBadge
{
    public static function refreshOn(): array
    {
        return [BadgeSourceModel::class, BadgeSourceEvent::class];
    }
}

class ThrowingBadge extends NavBadge
{
    public function count(): ?int
    {
        throw new RuntimeException('query failed');
    }
}

class NegativeBadge extends NavBadge
{
    public function count(): ?int
    {
        return -4;
    }
}

beforeEach(function () {
    config(['cache.default' => 'array']);
    CountingBadge::$value = 3;
    CountingBadge::$calls = 0;
    ScopedBadge::$viewer = 'a';
    // A new "request": the per-request memo lives on the repository singleton.
    $this->nextRequest = fn () => app()->forgetInstance(NavBadgeRepository::class);
    $this->item = fn (string $class, array $extra = []): array => ['id' => 'x', 'name' => 'X', 'badge' => $class, ...$extra];
});

it('returns the class count and caches it across requests', function () {
    expect(synav()->badge(($this->item)(CountingBadge::class)))->toBe(3);

    CountingBadge::$value = 9;
    ($this->nextRequest)();

    expect(synav()->badge(($this->item)(CountingBadge::class)))->toBe(3)
        ->and(CountingBadge::$calls)->toBe(1);
});

it('reads the cache once per request', function () {
    synav()->badge(($this->item)(UncachedBadge::class));
    synav()->badge(($this->item)(UncachedBadge::class));

    expect(CountingBadge::$calls)->toBe(1);
});

it('never caches with ttl 0 and keeps forever with ttl null', function () {
    synav()->badge(($this->item)(UncachedBadge::class));
    CountingBadge::$value = 9;
    ($this->nextRequest)();
    expect(synav()->badge(($this->item)(UncachedBadge::class)))->toBe(9);

    synav()->badge(($this->item)(ForeverBadge::class));
    CountingBadge::$value = 20;
    $this->travel(5)->years();
    ($this->nextRequest)();
    expect(synav()->badge(($this->item)(ForeverBadge::class)))->toBe(9);
});

it('expires after the ttl', function () {
    synav()->badge(($this->item)(CountingBadge::class));
    CountingBadge::$value = 9;
    $this->travel(3601)->seconds();
    ($this->nextRequest)();

    expect(synav()->badge(($this->item)(CountingBadge::class)))->toBe(9);
});

it('refreshes one badge for everyone, one scope only, or every badge', function () {
    synav()->badge(($this->item)(CountingBadge::class));
    synav()->badge(($this->item)(ScopedBadge::class));
    ScopedBadge::$viewer = 'b';
    ($this->nextRequest)();
    synav()->badge(($this->item)(ScopedBadge::class));

    CountingBadge::$value = 9;
    ScopedBadge::$values = ['a' => 2, 'b' => 6];

    synav()->refreshBadge(ScopedBadge::class, 'a');
    expect(synav()->badge(($this->item)(ScopedBadge::class)))->toBe(5);
    ScopedBadge::$viewer = 'a';
    ($this->nextRequest)();
    expect(synav()->badge(($this->item)(ScopedBadge::class)))->toBe(2)
        ->and(synav()->badge(($this->item)(CountingBadge::class)))->toBe(3);

    synav()->refreshBadge(CountingBadge::class);
    expect(synav()->badge(($this->item)(CountingBadge::class)))->toBe(9);

    CountingBadge::$value = 11;
    ScopedBadge::$values = ['a' => 7, 'b' => 8];
    synav()->refreshBadges();
    expect(synav()->badge(($this->item)(CountingBadge::class)))->toBe(11)
        ->and(synav()->badge(($this->item)(ScopedBadge::class)))->toBe(7);
});

it('forgets the cached count only once the surrounding transaction commits', function () {
    synav()->badge(($this->item)(CountingBadge::class));

    DB::transaction(function () {
        synav()->refreshBadge(CountingBadge::class);
        // Another request cannot see the uncommitted change yet: it must not recount and re-cache the old number.
        ($this->nextRequest)();
        synav()->badge(($this->item)(CountingBadge::class));
    });

    CountingBadge::$value = 9;
    ($this->nextRequest)();

    expect(synav()->badge(($this->item)(CountingBadge::class)))->toBe(9);
});

it('refreshes when a declared model is saved or deleted, or a declared event fires', function (Closure $trigger) {
    config(['synapps.nav.backend' => ['m' => ['id' => 'm', 'name' => 'M', 'sub' => ['x' => ($this->item)(ListeningBadge::class)]]]]);
    app(NavBadgeRepository::class)->registerListeners();

    synav()->badge(($this->item)(ListeningBadge::class));
    CountingBadge::$value = 9;
    $trigger();

    expect(synav()->badge(($this->item)(ListeningBadge::class)))->toBe(9);
})->with([
    'model saved' => [fn () => Event::dispatch('eloquent.saved: '.BadgeSourceModel::class, new BadgeSourceModel)],
    'model deleted' => [fn () => Event::dispatch('eloquent.deleted: '.BadgeSourceModel::class, new BadgeSourceModel)],
    'event' => [fn () => event(new BadgeSourceEvent)],
]);

it('sums only the children the user may see, plus its own badge', function () {
    $parent = ['id' => 'p', 'name' => 'P', 'sub' => [
        'a' => ($this->item)(CountingBadge::class, ['acl' => 'mod.a']),
        'b' => ($this->item)(NegativeBadge::class, ['acl' => 'mod.b']),
        'hidden' => ($this->item)(ForeverBadge::class, ['acl' => 'mod.hidden']),
        'plain' => ['id' => 'plain', 'name' => 'Plain'],
    ]];
    synav()->authorizeBadgesUsing(fn (string $acl): bool => $acl !== 'mod.hidden');

    expect(synav()->badge($parent))->toBe(3)
        ->and(synav()->badge(['id' => 'none', 'name' => 'None']))->toBe(0);
});

it('labels zero as nothing and caps at 99+', function (int $value, string $label) {
    CountingBadge::$value = $value;

    expect(synav()->badgeLabel(($this->item)(UncachedBadge::class)))->toBe($label);
})->with([[0, ''], [1, '1'], [99, '99'], [100, '99+'], [4521, '99+']]);

it('never breaks the menu: a missing, foreign or throwing class counts as zero and is logged', function (string $class) {
    Log::spy();

    expect(synav()->badge(($this->item)($class)))->toBe(0)
        ->and(synav()->badge(($this->item)($class)))->toBe(0);
    Log::shouldHaveReceived('warning')->once();
})->with([['App\\Missing\\Badge'], [stdClass::class], [ThrowingBadge::class]]);
