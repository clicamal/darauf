<?php

declare(strict_types=1);

return [
    'placeholder' => 'Darauf placeholder translation.',
    'error' => [
        'invalid_did' => 'Invalid DID.',
        'invalid_public_key' => 'Invalid public key.',
        'username_taken' => 'Username already taken.',
        'challenge_not_found' => 'Challenge was not generated or expired.',
        'did_document_not_found' => 'DID document not found.',
        'rsa_verification_method_not_found' => 'DID document does not have a RSA verification method.',
        'rsa_verification_failed' => 'RSA verification failed.',
        'general_error' => 'Error completing request.',
    ],
    'success' => [
        'create_did_document' => 'DID document created.',
        'did_subject_authenticated' => 'DID subject authenticated.',
    ],
];
