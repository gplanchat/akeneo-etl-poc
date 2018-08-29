<?php

namespace App\Loader;

use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Kiboko\Component\ETL\Loader\LoaderInterface;

class AkeneoEnterpriseAPIBatchLoader implements LoaderInterface
{
    /**
     * @var ProductApiInterface
     */
    private $client;

    private $batchSize = 100;

    /**
     * @param ProductApiInterface $client
     */
    public function __construct(ProductApiInterface $client)
    {
        $this->client = $client;
    }

    public function load(): \Generator
    {
        $index = 0;
        $batch = [];
        while ($batch[] = $line = yield) {
            if (++$index % $this->batchSize === 0 && $index > 1) {
                file_put_contents('php://stderr', sprintf('Sent products %d to %d.', $index - $this->batchSize, $index) . PHP_EOL, FILE_APPEND);

                $start = microtime(true);
                $this->client->upsertList($batch);
                $end = microtime(true);

                file_put_contents('php://stderr', sprintf(' > Request time: %f seconds.', $end - $start) . PHP_EOL, FILE_APPEND);
                $batch = [];
            }

            yield $line;
        }
    }
}
