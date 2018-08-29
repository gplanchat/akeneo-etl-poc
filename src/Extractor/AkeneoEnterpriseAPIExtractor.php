<?php

namespace App\Extractor;

use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Kiboko\Component\ETL\Extractor\ExtractorInterface;

class AkeneoEnterpriseAPIExtractor implements ExtractorInterface
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

    public function extract(): \Iterator
    {
        foreach ($this->client->all() as $product) {
            yield $product;
        }
    }
}
