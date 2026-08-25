<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $language_code
 * @property string $key
 * @property string $value
 * @property string|null $label
 *
 * @method static \Illuminate\Database\Eloquent\Collection<int, \VmEngine\Synapse\Models\Language> all($columns = ['*'])
 * @method static \VmEngine\Synapse\Models\Language create(array<string, mixed> $attributes)
 * @method static \VmEngine\Synapse\Models\Language updateOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
 * @method static \Illuminate\Database\Eloquent\Builder<\VmEngine\Synapse\Models\Language> where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static \VmEngine\Synapse\Models\Language findOrFail(int|string $id, array<int, string> $columns = ['*'])
 */
class Language extends Model
{
    protected $fillable = [
        'language_code',
        'key',
        'value',
        'label',
    ];
}
