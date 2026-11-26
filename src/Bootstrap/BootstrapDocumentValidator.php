<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Bootstrap;

/**
 * Fail-closed structural checks for the Nailpolishment-compatible bootstrap v1.
 * This does NOT establish trusted redirect destinations, client registrations or
 * complete JSON Schema validation. Do not expose a public endpoint yet.
 */
final class BootstrapDocumentValidator
{
    /** @param array<string,mixed> $document */
    public function validate(array $document): void
    {
        if (($document['version'] ?? null) !== 1
            || !is_array($document['api'] ?? null)
            || !is_array($document['oauth'] ?? null)
            || !is_array($document['oauth']['clients'] ?? null)
            || [] === $document['oauth']['clients']) {
            throw new \InvalidArgumentException('Unsupported client bootstrap contract.');
        }

        $api = $document['api']['base_url'] ?? null;
        $this->requireHttps($api);
        $apiPath = parse_url($api, PHP_URL_PATH);
        if (!is_string($apiPath) || !preg_match('~/api/?$~D', $apiPath)) {
            throw new \InvalidArgumentException('Invalid API base path.');
        }
        foreach ($document['oauth']['clients'] as $id => $client) {
            if (!is_string($id) || '' === trim($id) || !is_array($client)
                || !is_string($client['client_id'] ?? null)
                || !preg_match('/^[\x21-\x7e]{1,255}$/D', $client['client_id'])
                || ($client['authorization_code'] ?? null) !== true
                || ($client['pkce'] ?? null) !== 'S256'
                || !is_array($client['scopes'] ?? null) || [] === $client['scopes']) {
                throw new \InvalidArgumentException('Invalid public OAuth client.');
            }
            $this->requireHttps($client['issuer'] ?? null);
            if (array_key_exists('discovery_url', $client)) {
                $this->requireHttps($client['discovery_url']);
                // Discovery is allowed under a provider-specific path, but
                // never a different origin or a request-chosen host.
                if ($this->origin($client['issuer']) !== $this->origin($client['discovery_url'])) {
                    throw new \InvalidArgumentException('Issuer/discovery origin mismatch.');
                }
            }
            foreach ($client['scopes'] as $scope) {
                if (!is_string($scope) || !preg_match('/^[\x21\x23-\x5b\x5d-\x7e]+$/D', $scope)) {
                    throw new \InvalidArgumentException('Invalid OAuth scope.');
                }
            }
            if (!in_array('openid', $client['scopes'], true)
                || count($client['scopes']) !== count(array_unique($client['scopes']))) {
                throw new \InvalidArgumentException('Invalid OAuth scope set.');
            }
        }
    }

    private function requireHttps(mixed $url): void
    {
        if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)
            || str_contains($url, '\\') || preg_match('/[\x00-\x20\x7f]|%2f|%5c|%2e|\/\.{1,2}(?:\/|$)/i', $url)) {
            throw new \InvalidArgumentException('Invalid public URL.');
        }
        $parts = parse_url($url);
        if (!is_array($parts) || ($parts['scheme'] ?? null) !== 'https'
            || !is_string($parts['host'] ?? null) || '' === $parts['host']
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])) {
            throw new \InvalidArgumentException('A public HTTPS URL is required.');
        }
    }
    private function origin(string $url): string
    {
        $parts = parse_url($url);
        return strtolower((string) $parts['scheme']).'://'.strtolower((string) $parts['host'])
            .':'.(string) ($parts['port'] ?? 443);
    }

}
