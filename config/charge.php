<?php

return [
    /*
     * When a user subscribes to a plan, which role should they be given?
     */
    'roles_and_plans' => [
        [
            'plan' => 'prod_O4scSOs7KYSern',
            'role' => 'member',
        ],
        [
            'plan' => 'prod_O4sdyd7PVXOLYb',
            'role' => 'member',
        ],
        [
            'plan' => 'prod_O4sd98aywfwlFg',
            'role' => 'member',
        ],
        [
            'plan' => 'prod_OArkw2xxT0kAc0',
            'role' => 'member',
            'title' => 'Lifetime Membership'
        ],
    ],

    // send email address for all Charge emails
    'email' => [
        'sender' => '',

        /*
         * For all below, which template & subject to use for which email.
         * Set to null to not send that email
         */
        'one_time_payment_template' => '',
        'one_time_payment_subject' => '',

        'subscription_created' => [
            'template' => '',
            'subject' => '',
        ],
        'subscription_updated' => [
            'template' => '',
            'subject' => '',
        ],
        'subscription_canceled' => [
            'template' => '',
            'subject' => '',
        ],
        'subscription_upcoming' => [
            'template' => '',
            'subject' => '',
        ],
        'subscription_payment_succeeded' => [
            'template' => '',
            'subject' => '',
        ],
        'subscription_payment_failed' => [
            'template' => '',
            'subject' => '',
        ],
        'customer_updated' => [
            'template' => '',
            'subject' => '',
        ],
    ]
];
