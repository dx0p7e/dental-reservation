<?php

return [
    'rp_uuid' => env('SMARTID_RP_UUID', '00000000-0000-4000-8000-000000000000'),
    'rp_name' => env('SMARTID_RP_NAME', 'DEMO'),
    'host_url' => env('SMARTID_HOST_URL', 'https://sid.demo.sk.ee/smart-id-rp/v3'),
    'ocsp_enabled' => env('SMARTID_OCSP_ENABLED', false),
    'demo_auto_confirm' => env('SMARTID_DEMO_AUTO_CONFIRM', true),
];
