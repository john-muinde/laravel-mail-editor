<?php

use Qoraiche\MailEclipse\MailEclipse;


test('valid class name', function () {
    $expectedMap = [
        'mail' => false,
        '1 Number' => false,
        'Number 1' => 'Number1Mail',
        'Welcome #1 User' => 'Welcome1UserMail',
        'Welcome User' => 'WelcomeUserMail',
        'null' => 'NullMail',
        '_null' => 'NullMail',
        '#null' => 'NullMail',
        'CustomerMail' => 'Customermail',
        'Customermail' => 'Customermail',
        'Customer Mail' => 'CustomerMail',
        'customer mail' => 'CustomerMail',
    ];

    foreach ($expectedMap as $input => $expected) {
        $className = MailEclipse::generateClassName($input);
        expect($className)->toEqual($expected);
    }
});
