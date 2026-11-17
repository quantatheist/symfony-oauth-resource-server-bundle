<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Bootstrap;

/**
 * Strict v1 public bootstrap contract.
 *
 * Fail closed and reject unexpected keys: this prevents accidental disclosure of
 * application-only config (secrets, access policies, internal service URLs).
 *
 * The host must explicitly configure the public document. No URLs are fetched
 * server-side, and this validator does not replace client-side trust decisions.
 */
final class BootstrapDocumentValidator
{
    private const ROOT_KEYS = ['version', 'api', 'oauth'];
    private const API_KEYS = ['base_url'];
    private const OAUTH_KEYS = ['clients'];
    private const CLIENT_KEYS = [
        'issuer', 'discovery_url', 'client_id', 'authorization_code', 'pkce', 'scopes',
    ];

    /** @param array<string, mixed> $document */
    public function validate(array $document): void
    {
        $this->onlyKeys($document, self::ROOT_KEYS);
        if (($document['version'] ?? null) !== 1
            || !is_array($document['api'] ?? null)
            || !is_array($document['oauth'] ?? null)) {
            throw new \InvalidArgumentException('Unsupported client bootstrap contract.');
        }

        $this->onlyKeys($document['api'], self::API_KEYS);
        $this->onlyKeys($document['oauth'], self::OAUTH_KEYS);
        $this->requireHttpsUrl($document['api']['base_url'] ?? null);

        $clients = $document['oauth']['clients'] ?? null;
        if (!is_array($clients) || [] === $clients || array_is_list($clients)) {
            throw new \InvalidArgumentException('At least one named OAuth client is required.');
        }

        foreach ($clients as $name => $client) {
            if (!is_string($name) || !preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $name)
                || !is_array($client)) {
                throw new \InvalidArgumentException('Invalid public OAuth client name.');
            }

            $this->onlyKeys($client, self::CLIENT_KEYS);
            if (!is_string($client['client_id'] ?? null)
                || '' === trim($client['client_id'])
                || trim($client['client_id']) !== $client['client_id']
                || ($client['authorization_code'] ?? null) !== true
                || ($client['pkce'] ?? null) !== 'S256') {
                throw new \InvalidArgumentException('Invalid public OAuth client.');
            }

            $this->requireHttpsUrl($client['issuer'] ?? null);
            if (array_key_exists('discovery_url', $client)) {
                $this->requireHttpsUrl($client['discovery_url']);
            }

            $scopes = $client['scopes'] ?? null;
            if (!is_array($scopes) || [] === $scopes || !array_is_list($scopes)) {
                throw new \InvalidArgumentException('Scopes must be a nonempty list.');
            }

            foreach ($scopes as $scope) {
                if (!is_string($scope)
                    || !preg_match('/^[\x21\x23-\x5b\x5d-\x7e]+$/D', $scope)) {
                    throw new \InvalidArgumentException('Invalid OAuth scope.');
                }
            }

            if (count($scopes) !== count(array_unique($scopes))) {
                throw new \InvalidArgumentException('Duplicate OAuth scope.');
            }

            if (!in_array('openid', $scopes, true)) {
                throw new \InvalidArgumentException('OIDC clients must request openid.');
            }
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param list<string> $allowedKeys
     */
    private function onlyKeys(array $input, array $allowedKeys): void
    {
        foreach (array_keys($input) as $key) {
            if (!is_string($key) || !in_array($key, $allowedKeys, true)) {
                throw new \InvalidArgumentException('Unknown key in public bootstrap.');
            }
        }
    }

    private function requireHttpsUrl(mixed $url): void
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid public URL.');
        }

        $parts = parse_url($url);
        if (!is_array($parts)
            || ($parts['scheme'] ?? null) !== 'https'
            || !is_string($parts['host'] ?? null)
            || '' === $parts['host']
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            throw new \InvalidArgumentException('A public HTTPS URL is required.');
        }
    }
}
