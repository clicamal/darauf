<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Did;

use Clicamal\Darauf\Exceptions\ValidationException;

class DidUrlDomainModel
{
    public const string DID_URL_REGEX = '~^(did:[^/?#]+)(/[^?#]*)?(?:\?([^#]*))?(?:#(.*))?$~D';

    private DidDomainModel $did;

    private string $path;

    private ?string $query;

    private ?string $fragment;

    public function __construct(string $didUrl)
    {
        if (preg_match(self::DID_URL_REGEX, $didUrl, $parts) !== 1) {
            throw new ValidationException('invalid_did_url');
        }

        $this->did = new DidDomainModel($parts[1]);

        $this->path = isset($parts[2]) && $parts[2] !== '' ? $parts[2] : '';
        $this->query = isset($parts[3]) && $parts[3] !== '' ? $parts[3] : null;
        $this->fragment = isset($parts[4]) && $parts[4] !== '' ? $parts[4] : null;

        foreach ([$this->path, $this->query, $this->fragment] as $component) {
            if ($component !== null && (
                preg_match('/%(?![0-9A-Fa-f]{2})/', $component) ||
                preg_match('/[\x00-\x20\x7f]/', $component)
            )) {
                throw new ValidationException('invalid_did_url_component');
            }
        }
    }

    public static function validate(string $didUrl): bool
    {
        return preg_match(self::DID_URL_REGEX, $didUrl) === 1;
    }

    public function getDid(): DidDomainModel
    {
        return $this->did;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    public function getFull(): string
    {
        $full = $this->did->getFull();

        if ($this->path !== '') {
            $full .= $this->path;
        }

        if ($this->query !== null) {
            $full .= '?'.$this->query;
        }

        if ($this->fragment !== null) {
            $full .= '#'.$this->fragment;
        }

        return $full;
    }
}
