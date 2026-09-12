<?php

declare(strict_types=1);

return [
    'error' => [
        'general' => 'Something went wrong.',
        'method_not_supported' => 'DID method not supported.',
        'invalid_did' => 'Invalid DID.',
        'invalid_did_document' => 'Invalid DID Document.',
        'invalid_did_url' => 'Invalid DID URL.',
        'invalid_did_url_component' => 'Invalid DID URL component.',
        'invalid_did_web_document' => 'Invalid DID Web document.',
        'invalid_did_web_path' => 'Invalid DID Web path.',
        'challenge_not_found' => 'Challenge was not generated or expired.',
        'challenge_generation_failed' => 'Failed to generate challenge.',
        'challenge_verification_failed' => 'Challenge verification failed.',
        'invalid_multibase_key' => 'Invalid multibase key.',
        'invalid_jwk' => 'Invalid JWK.',
        'resource_not_found' => 'Resource not found.',
        'verification_method_not_supported' => 'Verification method not supported.',
    ],
    'success' => [
        'challenge_verified' => 'Challenge verified successfully.',
        'did_document_registered' => 'DID document registered.',
    ],
];
