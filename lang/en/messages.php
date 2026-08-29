<?php

declare(strict_types=1);

return [
    'placeholder' => 'Darauf placeholder translation.',
    'error' => [
        'rsa_verification' => [
            'invalid_public_key' => 'Invalid public key.',
            'challenge_not_found' => 'Challenge was not generated or expired.',
            'rsa_verification_method_not_found' => 'DID document does not have a RSA verification method.',
            'rsa_verification_failed' => 'RSA verification failed.',
        ],
        'invalid_did' => 'Invalid DID.',
        'username_taken' => 'Username already taken.',
        'did_document_not_found' => 'DID document not found.',
        'general_error' => 'Error completing request.',
    ],
    'success' => [
        'create_did_document' => 'DID document created.',
        'did_subject_authenticated' => 'DID subject authenticated.',
    ],
];
