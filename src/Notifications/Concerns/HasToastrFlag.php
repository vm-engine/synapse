<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Notifications\Concerns;

/**
 * Give a Notification class a `toastr` flag that Notify::withToastr() can
 * set before sending. The notification's own toArray()/toDatabase() should
 * include `'toastr' => $this->toastr ?? false` in its returned data so the
 * synotif::bell component knows whether to pop a toast for it.
 */
trait HasToastrFlag
{
    public ?bool $toastr = null;
}
