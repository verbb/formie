<?php

// System email messages registered in Formie::_registerEmailMessages().
//
// Kept separate from extracted strings because Formie registers these by message ID,
// not by English source text — the extractor cannot recover the copy.
// plugin-translator merges this file into translations/{locale}/formie.php on regen.
return [
    'formie_failed_notification_heading' => 'When an email notification fails to send:',
    'formie_failed_notification_subject' => 'Email notification failed to send for form "{{ form.title }}" on {{ siteName }}.',
    'formie_failed_notification_body' => "An email notification for the form “{{ form.title }}” has failed to send.\n\n" .
        "The error response was recorded: {{ emailResponse | json_encode }}.\n\n" .
        "To review it please log into your control panel.\n\n" .
        '{{ submission.cpEditUrl }}',
    'formie_failed_integration_heading' => 'When an integration fails to send:',
    'formie_failed_integration_subject' => 'Integration failed for form "{{ form.title }}" on {{ siteName }}.',
    'formie_failed_integration_body' => "The “{{ integration.name }}” integration for the form “{{ form.title }}” has failed.\n\n" .
        "The error was: {{ errorMessage }}\n\n" .
        "{% if integrationResponse %}The response was recorded: {{ integrationResponse | json_encode }}.\n\n{% endif %}" .
        "{% if queueJobId %}Queue job ID: {{ queueJobId }}\n\n{% endif %}" .
        "To review it please log into your control panel.\n\n" .
        '{{ submission.cpEditUrl }}',
];
