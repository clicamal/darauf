<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Did\Resolvers;

use Clicamal\Darauf\Did\DidDomainModel;
use Clicamal\Darauf\Did\DidResolverContract;
use Clicamal\Darauf\Did\DidUrlDomainModel;
use Clicamal\Darauf\Exceptions\DidResolutionException;
use Clicamal\Darauf\Models\Authentication;
use Clicamal\Darauf\Models\DidDocument;
use Clicamal\Darauf\Models\VerificationMethod;

class DidWebResolver implements DidResolverContract
{
    public const string REGEX_DID_WEB_AUTHORITY = '/^(?=.{1,253}$)(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)*[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?(?::[1-9][0-9]{0,4})?$/';

    public function resolve(DidDomainModel $did): ?DidDocument
    {
        if ($did->getMethodName() !== 'web') {
            throw new DidResolutionException('invalid_did');
        }

        $identifier = substr($did->getFull(), 9);
        $parts = explode(':', $identifier);
        $authority = rawurldecode(array_shift($parts));

        if (filter_var($authority, FILTER_VALIDATE_IP) !== false || ! preg_match(
            self::REGEX_DID_WEB_AUTHORITY,
            $authority,
        )) {
            throw new DidResolutionException('invalid_did_web_document');
        }

        $path = $parts === [] ? '/.well-known/did.json' : '/'.implode('/', array_map(
            static function (string $part): string {
                if ($part === '' || str_contains($part, '/')) {
                    throw new DidResolutionException('invalid_did_web_path');
                }

                return rawurlencode(rawurldecode($part));
            },
            $parts,
        ));

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'ignore_errors' => true,
                'header' => "Accept: application/did+json, application/json\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $contents = @file_get_contents('https://'.$authority.$path, false, $context);

        if ($contents === false) {
            return null;
        }

        $document = json_decode($contents, true);

        if (! is_array($document) || ! isset($document['id']) || $document['id'] !== $did->getFull()) {
            throw new DidResolutionException('invalid_did_web_document');
        }

        return new DidDocument([
            'did_document_id' => $did->getFull(),
            'serialized' => json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Dereferences a DID URL and returns the corresponding verification method or authentication.
     */
    public function dereference(DidUrlDomainModel $didUrl): VerificationMethod|Authentication|null
    {
        $didDocument = $this->resolve($didUrl->getDid());

        if ($didDocument === null) {
            return null;
        }

        $deserializedDid = $didDocument->payload;

        foreach ([
            'verificationMethod', 'authentication',
        ] as $method) {
            if (isset($deserializedDid[$method]) && is_array($deserializedDid[$method])) {
                foreach ($deserializedDid[$method] as $item) {
                    if (isset($item['id']) && $item['id'] === $didUrl->getFull()) {
                        return match ($method) {
                            'verificationMethod' => new VerificationMethod([
                                'did_document_id' => $didDocument->did_document_id,
                                'serialized' => json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                            ]),
                            'authentication' => new Authentication([
                                'did_document_id' => $didDocument->did_document_id,
                                'serialized' => json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                            ]),
                        };
                    }
                }
            }
        }

        return null;
    }
}
