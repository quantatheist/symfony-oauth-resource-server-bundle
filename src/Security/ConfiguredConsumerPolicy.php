<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Security;

/**
 * Host-configured authorization policy for ALREADY VERIFIED access tokens.
 *
 * Does not parse JWTs or replace signature, token-purpose, issuer, expiry or
 * introspection validation. Those checks must precede this policy.
 */
final readonly class ConfiguredConsumerPolicy implements ConsumerPolicyInterface
{
    /**
     * @param array<string, array{issuer: string, audience: string, client_ids: list<string>, scopes: list<string>}> $profiles
     */
    public function __construct(private array $profiles)
    {
        foreach ($profiles as $name => $profile) {
            if (!is_string($name) || $name === '' || !is_array($profile)
                || !is_string($profile['issuer'] ?? null) || trim($profile['issuer']) === ''
                || !is_string($profile['audience'] ?? null) || trim($profile['audience']) === ''
                || !is_array($profile['client_ids'] ?? null) || !array_is_list($profile['client_ids']) || $profile['client_ids'] === []
                || !is_array($profile['scopes'] ?? null) || !array_is_list($profile['scopes'])) {
                throw new \InvalidArgumentException('Invalid API consumer policy.');
            }
            foreach ([...$profile['client_ids'], ...$profile['scopes']] as $value) {
                if (!is_string($value) || $value === '' || trim($value) !== $value) {
                    throw new \InvalidArgumentException('Invalid API consumer policy value.');
                }
            }
        }
    }

    public function accepts(VerifiedAccessToken $token, string $consumerId): bool
    {
        $profile = $this->profiles[$consumerId] ?? null;
        if ($profile === null || !hash_equals($profile['issuer'], $token->issuer)
            || !in_array($profile['audience'], $token->audiences, true)) {
            return false;
        }
        $clientId = $token->claims['client_id'] ?? null;
        $azp = $token->claims['azp'] ?? null;
        if (!is_string($clientId) || $clientId === '' || !in_array($clientId, $profile['client_ids'], true)
            || !is_string($azp) || !hash_equals($clientId, $azp)) {
            return false;
        }
        $scope = $token->claims['scope'] ?? null;
        if (!is_string($scope)) {
            return false;
        }
        $scopes = preg_split('/ +/', trim($scope));
        if (!is_array($scopes)) {
            return false;
        }
        foreach ($profile['scopes'] as $required) {
            if (!in_array($required, $scopes, true)) {
                return false;
            }
        }
        return true;
    }
}
