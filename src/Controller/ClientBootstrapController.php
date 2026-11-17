<?php

declare(strict_types=1);

namespace Quantatheist\OAuthResourceServer\Controller;

use Quantatheist\OAuthResourceServer\Bootstrap\BootstrapDocumentValidator;
use Quantatheist\OAuthResourceServer\Bootstrap\ClientBootstrapProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class ClientBootstrapController
{
    public function __construct(
        private ClientBootstrapProviderInterface $provider,
        private BootstrapDocumentValidator $validator,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {
            $document = $this->provider->publicDocument();
            $this->validator->validate($document);

            return new JsonResponse($document, 200, [
                'Cache-Control' => 'public, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
                'Vary' => 'Accept-Encoding',
            ]);
        } catch (\Throwable) {
            // Do not disclose secrets or the server-side configuration.
            return new JsonResponse(['error' => 'bootstrap_unavailable'], 503, [
                'Cache-Control' => 'no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }
    }
}
