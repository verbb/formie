<?php

return [
    //
    // Email Messages
    //

    'formie_failed_notification_heading' => 'When an email notification fails to send:',
    'formie_failed_notification_subject' => 'Email notification failed to send for form "{{ form.title }}" on {{ siteName }}.',
    'formie_failed_notification_body' => "An email notification for the form “{{ form.title }}” has failed to send.\n\n" . 
        "The error response was recorded: {{ emailResponse | json_encode }}.\n\n" .
        "To review it please log into your control panel.\n\n" .
        "{{ submission.cpEditUrl }}",
];

