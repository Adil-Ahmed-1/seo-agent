<?php

return [
    'free' => [
        'name'            => 'Free',
        'price'           => 0,
        'websites'        => 1,
        'blogs_per_month' => 2,
        'features'        => ['AI Blog Writing', 'Manual Publishing'],
    ],
    'starter' => [
        'name'            => 'Starter',
        'stripe_price_id' => env('STRIPE_STARTER_PRICE'),
        'price'           => 29,
        'websites'        => 1,
        'blogs_per_month' => 10,
        'features'        => ['AI Blog Writing', 'Basic SEO', 'WordPress Publishing'],
    ],
    'pro' => [
        'name'            => 'Pro',
        'stripe_price_id' => env('STRIPE_PRO_PRICE'),
        'price'           => 79,
        'websites'        => 5,
        'blogs_per_month' => 30,
        'features'        => ['Everything in Starter', 'Auto-Publishing', 'Keyword Research'],
    ],
    'agency' => [
        'name'            => 'Agency',
        'stripe_price_id' => env('STRIPE_AGENCY_PRICE'),
        'price'           => 199,
        'websites'        => 25,
        'blogs_per_month' => -1,
        'features'        => ['Everything in Pro', 'White Label', 'API Access'],
    ],
];