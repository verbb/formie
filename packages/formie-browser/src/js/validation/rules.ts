import type { ValidationRuleDefinition } from '#validation/types';
import email from '#validation/rules/email';
import match from '#validation/rules/match';
import number from '#validation/rules/number';
import required from '#validation/rules/required';
import url from '#validation/rules/url';

export default {
    // Keep the core validator registry centralized so FormieValidator can extend
    // it at runtime while still shipping one predictable builtin rule surface.
    required,
    email,
    url,
    number,
    match,
} satisfies Record<string, ValidationRuleDefinition>;
