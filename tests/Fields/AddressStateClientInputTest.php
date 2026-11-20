<?php

declare(strict_types=1);

use verbb\formie\fields\subfields\AddressState;

// Client-rendered forms load the chosen country's subdivisions for State / Province from the same action as the
// server-rendered module, so the client definition says where it is when the part follows the country.
it('sends the subdivisions action only when State / Province follows the country', function(): void {
    $dynamic = (new AddressState(['handle' => 'state', 'inputMode' => AddressState::INPUT_MODE_DROPDOWN_WHEN_AVAILABLE]))->getClientRenderedInput();
    $text = (new AddressState(['handle' => 'state']))->getClientRenderedInput();

    expect($dynamic['inputMode'])->toBe('dropdownWhenAvailable')
        ->and($dynamic['subdivisionsUrl'])->toContain('formie/address/subdivisions')
        ->and($text['subdivisionsUrl'])->toBeNull();
});
