<?php

namespace Intaro\CustomIndexBundle\Command;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Intaro\CustomIndexBundle\DBAL\ExtendedPlatform;
use Intaro\CustomIndexBundle\DBAL\QueryExecutor;
use Intaro\CustomIndexBundle\DTO\CustomIndex;
use Intaro\CustomIndexBundle\Metadata\ReaderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand('intaro:doctrine:index:update', 'Create new and drop not existing custom indexes')]
class IndexUpdateCommand extends Command
{
    private const DUMP_SQL_OPTION = 'dump-sql';

    private ?InputInterface $input;
    private ?OutputInterface $output;

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly ReaderInterface $reader,
        private readonly QueryExecutor $queryExecutor,
        private readonly bool $searchInAllSchemas,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(self::DUMP_SQL_OPTION, null, InputOption::VALUE_NONE, 'Dump sql instead creating index');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $connection = $this->em->getConnection();
        $platform = $this->createExtendedPlatform($connection->getDatabasePlatform());
        $indexesNames = $this->queryExecutor->getIndexesNames($platform, $this->searchInAllSchemas);
        $currentSchema = $this->queryExecutor->getCurrentSchema($platform);
        $customIndexes = $this->reader->getIndexes($currentSchema, $this->searchInAllSchemas);

        $this->dropIndexes($indexesNames, $customIndexes, $platform);
        $this->createIndexes($indexesNames, $customIndexes, $platform);

        return Command::SUCCESS;
    }

    /**
     * @param array<string>              $indexesNames
     * @param array<string, CustomIndex> $customIndexes
     */
    private function createIndexes(array $indexesNames, array $customIndexes, ExtendedPlatform $platform): void
    {
        $createFlag = false;
        foreach ($customIndexes as $name => $index) {
            if (!in_array($name, $indexesNames, true)) {
                $this->createIndex($platform, $index);
                $createFlag = true;
            }
        }
        if (!$createFlag) {
            $this->output->writeln('<info>No index was created</info>');
        }
    }

    /**
     * @param array<string>              $indexesNames
     * @param array<string, CustomIndex> $customIndexes
     */
    private function dropIndexes(array $indexesNames, array $customIndexes, ExtendedPlatform $platform): void
    {
        $dropFlag = false;
        foreach ($indexesNames as $indexName) {
            if (!array_key_exists($indexName, $customIndexes)) {
                $this->dropIndex($platform, $this->quoteSchema($indexName));
                $dropFlag = true;
            }
        }

        if (!$dropFlag) {
            $this->output->writeln('<info>No index was dropped.</info>');
        }
    }

    private function dropIndex(ExtendedPlatform $platform, string $indexName): void
    {
        if ($this->input->getOption(self::DUMP_SQL_OPTION)) {
            $this->output->writeln($platform->getDropIndexSQL($indexName) . ';');

            return;
        }

        $this->queryExecutor->dropIndex($platform, $indexName);
        $this->output->writeln('<info>Index ' . $indexName . ' was dropped.</info>');
    }

    private function createIndex(ExtendedPlatform $platform, CustomIndex $index): void
    {
        $errors = $this->validator->validate($index);
        if (!count($errors)) {
            if ($this->input->getOption(self::DUMP_SQL_OPTION)) {
                $this->output->writeln($platform->createIndexSQL($index) . ';');

                return;
            }

            $this->queryExecutor->createIndex($platform, $index);
            $this->output->writeln('<info>Index ' . $index->getName() . ' was created.</info>');

            return;
        }

        $this->output->writeln('<error>Index ' . $index->getName() . ' was not created.</error>');

        foreach ($errors as $error) {
            $this->output->writeln('<error>' . $error->getMessage() . '</error>');
        }
    }

    private function quoteSchema(string $name): string
    {
        $parts = explode('.', $name);
        $parts[0] = '"' . $parts[0] . '"';

        return implode('.', $parts);
    }

    private function createExtendedPlatform(AbstractPlatform $platform): ExtendedPlatform
    {
        return match (true) {
            $platform instanceof PostgreSQLPlatform => new ExtendedPlatform(),
            default => throw new \LogicException(sprintf('Platform %s does not support', $platform::class)),
        };
    }
}
