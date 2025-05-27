<?php

return [
    'merchant_code' => env('HESABE_MERCHANT_CODE'),
    'access_code'   => env('HESABE_ACCESS_CODE'),
    'secret_key'    => env('HESABE_SECRET_KEY'),
    'iv_key'        => env('HESABE_IV_KEY'),
    'env'           => env('HESABE_ENV', 'sandbox'),
    'base_url'      => env('HESABE_BASE_URL', 'https://sandbox.hesabe.com'),
];