<?php

namespace App\Command;

use App\Repository\ProductRepository;
use Elastica\Document;
use JoliCode\Elastically\IndexBuilder;
use JoliCode\Elastically\Indexer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-index',
    description: 'create elasticsearch index',
)]
class CreateIndexCommand extends Command
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly IndexBuilder $indexBuilder,
        private readonly Indexer $indexer
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $newIndex = $this->indexBuilder->createIndex("product");

        $allProducts = $this->productRepository->findAll();

        foreach ($allProducts as $product) {
            $this->indexer->scheduleIndex($newIndex, new Document($product->getId(), $product->toProductModel()));
        }

        $this->indexer->flush();

        $this->indexBuilder->markAsLive($newIndex, "product");
        $this->indexBuilder->speedUpRefresh($newIndex);
        $this->indexBuilder->purgeOldIndices("product");


        $io->success('success !');

        return Command::SUCCESS;
    }
}
