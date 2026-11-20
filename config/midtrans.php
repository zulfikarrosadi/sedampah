<?php

return [
  'server_key' => env('MIDTRANS_SERVER_KEY', ''),
  'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
  'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
  'is_sanitized' => true,
  'is_3ds' => true,

  'snap_base_url' => env('MIDTRANS_IS_PRODUCTION', false)
    ? 'https://app.midtrans.com/snap/v1'
    : 'https://app.sandbox.midtrans.com/snap/v1',

  'snap_js_url' => env('MIDTRANS_IS_PRODUCTION', false)
    ? 'https://app.midtrans.com/snap/snap.js'
    : 'https://app.sandbox.midtrans.com/snap/snap.js',
];
