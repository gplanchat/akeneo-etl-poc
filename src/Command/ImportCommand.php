<?php

namespace App\Command;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use App\Extractor\SplCSVExtractor;
use App\Loader\AkeneoEnterpriseAPIBatchLoader;
use Kiboko\Component\ETL\Pipeline\Pipeline;
use Kiboko\Component\ETL\Pipeline\PipelineRunner;
use Kiboko\Component\ETL\Transformer\CallableTransformer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends ContainerAwareCommand
{
    public static function getDefaultName()
    {
        return 'kiboko:import';
    }

    protected function configure()
    {
        $this
            ->setDescription('Imports data into Akeneo through API')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED
            )
            ->addOption(
                'client',
                null,
                InputOption::VALUE_REQUIRED
            )
            ->addOption(
                'secret',
                null,
                InputOption::VALUE_REQUIRED
            )
            ->addOption(
                'user',
                null,
                InputOption::VALUE_REQUIRED
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pipeline = new Pipeline(
            new PipelineRunner()
        );

        /** @var AkeneoPimEnterpriseClientInterface $client */
        $client = (new AkeneoPimEnterpriseClientBuilder('http://192.168.99.100:8080/'))
            ->buildAuthenticatedByPassword(
                $input->getOption('client'),
                $input->getOption('secret'),
                $input->getOption('user'),
                $input->getOption('password')
            );

        $pipeline
            ->extract(new SplCSVExtractor(new \SplFileObject($input->getOption('file'), 'r'), ';', '"', '"'))
            ->transform(new CallableTransformer(function($line) {
                return [
                    'identifier' => $line['sku'] ?? null,
                    'enabled' => true,
                    'family' => $line['family'] ?? null,
                    'values' => [
                        'name' =>  [
                            [
                                'locale' => null,
                                'scope' => null,
                                'data' => $line['name'] ?? null,
                            ],
                        ],
                        'short_description' =>  [
                            [
                                'locale' => 'en_US',
                                'scope' => 'ecommerce',
                                'data' => $line['short_description-en_US-ecommerce'] ?? null,
                            ],
                            [
                                'locale' => 'fr_FR',
                                'scope' => 'ecommerce',
                                'data' => $line['short_description-fr_FR-ecommerce'] ?? null,
                            ],
                        ],
                        'description' =>  [
                            [
                                'locale' => 'en_US',
                                'scope' => 'ecommerce',
                                'data' => $line['description-en_US-ecommerce'] ?? null,
                            ],
                            [
                                'locale' => 'fr_FR',
                                'scope' => 'ecommerce',
                                'data' => $line['description-fr_FR-ecommerce'] ?? null,
                            ],
                        ],
                        'price' =>  [
                            [
                                'locale' => null,
                                'scope' => null,
                                'data' => [
                                    [
                                        'amount' => (float) $line['price-USD'] ?? null,
                                        'currency' => 'USD'
                                    ],
                                    [
                                        'amount' => (float) $line['price-EUR'] ?? null,
                                        'currency' => 'EUR'
                                    ],
                                ],
                            ],
                        ],
                    ]
                ];
             }))
//            ->load(new StdoutLoader())
//            ->load(new AkeneoEnterpriseAPILoader($client->getProductApi()))
            ->load(new AkeneoEnterpriseAPIBatchLoader($client->getProductApi()))
            ->run();
    }
}
