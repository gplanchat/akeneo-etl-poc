<?php

namespace App\Command;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use App\Extractor\AkeneoEnterpriseAPIExtractor;
use App\Extractor\SplCSVExtractor;
use App\Loader\AkeneoEnterpriseAPIBatchLoader;
use Kiboko\Component\ETL\Loader\SplCSVLoader;
use Kiboko\Component\ETL\Loader\StdoutLoader;
use Kiboko\Component\ETL\Pipeline\Pipeline;
use Kiboko\Component\ETL\Pipeline\PipelineRunner;
use Kiboko\Component\ETL\Transformer\CallableTransformer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends ContainerAwareCommand
{
    public static function getDefaultName()
    {
        return 'kiboko:export';
    }

    protected function configure()
    {
        $this
            ->setDescription('Exports data from Akeneo through API')
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
            ->extract(new AkeneoEnterpriseAPIExtractor($client->getProductApi()))
            ->transform(new CallableTransformer(function($line) {
                return [
                    'sku' => $line['identifier'],
                    'family' => $line['family'],
                    'name' => $line['values']['name'][0]['data'] ?? null,
                    'short_description-en_US-ecommerce' => $line['values']['short_description'][0]['data'] ?? null,
                    'short_description-fr_FR-ecommerce' => $line['values']['short_description'][1]['data'] ?? null,
                    'description-en_US-ecommerce' => $line['values']['description'][0]['data'] ?? null,
                    'description-fr_FR-ecommerce' => $line['values']['description'][1]['data'] ?? null,
                    'price-USD' => $line['values']['price'][0]['data'][0]['amount'] ?? null,
                    'price-EUR' => $line['values']['price'][0]['data'][1]['amount'] ?? null,
                ];
            }))
//            ->load(new StdoutLoader())
            ->load(new SplCSVLoader(new \SplFileObject('exported.csv', 'w')))
            ->run();
    }
}
