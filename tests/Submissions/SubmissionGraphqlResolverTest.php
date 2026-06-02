<?php

declare(strict_types=1);

use verbb\formie\gql\resolvers\SubmissionResolver;

it('preserves underscored form handles when inferring graphql inline fragment context', function (): void {
    expect(SubmissionResolver::formHandleFromFragmentType('contact_us_Submission'))
        ->toBe('contact_us')
        ->and(SubmissionResolver::formHandleFromFragmentType('contact_Submission'))->toBe('contact')
        ->and(SubmissionResolver::formHandleFromFragmentType('SubmissionInterface'))->toBeNull();
});
