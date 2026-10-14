<?php

declare(strict_types=1);

use Illuminate\Notifications\Messages\MailMessage;

/** The framework's notification mail template prints these through JSON translations. */
function renderIndonesianMail(): string
{
    app()->setLocale('id');

    return (string) (new MailMessage)
        ->line('Isi pesan.')
        ->action('Atur kata sandi', 'https://example.test/reset')
        ->render();
}

afterEach(fn () => app()->setLocale('en'));

it('renders the notification mail footer and subcopy in Indonesian', function () {
    $html = renderIndonesianMail();

    expect($html)->toContain('Hak cipta dilindungi.')
        ->toContain('salin dan tempel URL di bawah ini')
        ->not->toContain('All rights reserved.')
        ->not->toContain('copy and paste the URL below');
});

it('translates the default greeting and salutation', function () {
    app()->setLocale('id');

    expect(__('Hello!'))->toBe('Halo!')
        ->and(__('Whoops!'))->toBe('Ups!')
        ->and(__('Regards,'))->toBe('Salam,');
});
