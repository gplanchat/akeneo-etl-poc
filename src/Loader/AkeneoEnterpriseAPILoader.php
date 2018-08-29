<?php

namespace App\Loader;

use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Kiboko\Component\ETL\Loader\LoaderInterface;

class AkeneoEnterpriseAPILoader implements LoaderInterface
{
    /**
     * @var ProductApiInterface
     */
    private $client;

    /**
     * @param ProductApiInterface $client
     */
    public function __construct(ProductApiInterface $client)
    {
        $this->client = $client;
    }

    public function load(): \Generator
    {
        while ($line = yield) {
            $identifier = $line['identifier'];
            unset($line['identifier']);

            $this->client->upsert(
                $identifier,
                $line
            );

            yield $line;
        }
    }
}
