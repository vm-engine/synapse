<?php

declare(strict_types=1);

namespace SynApps\Modules\Todos\Jobs\Middleware;

class RateLimitJobs
{
    /**
     * Process the queued job.
     */
    public function handle(object $job, callable $next): void
    {
        $next($job);
    }
}
