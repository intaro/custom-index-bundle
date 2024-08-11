<?php
namespace Intaro\CustomIndexBundle\Command;

use Intaro\CustomIndexBundle\Annotations\CustomIndexes;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\ConstraintViolation;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Connection;

use Intaro\CustomIndexBundle\DBAL\Schema\CustomIndex;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand('intaro:doctrine:index:update', 'Create new and drop not existing custom indexes')]
class IndexUpdateCommand extends Command
{
    private const DUMP_SQL_OPTION = 'dump-sql';

    // array with abstract classes
    protected $abstractClasses = [];

    //InputInterface
    protected $input;

    //OutputInterface
    protected $output;

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $em,
        private readonly bool $searchInAllSchemas
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(self::DUMP_SQL_OPTION, null, InputOption::VALUE_NONE, 'Dump sql instead creating index');
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $connection = $this->em->getConnection();

        $indexesInDb = $this->getCustomIndexesFromDb($connection);
        $indexesInModel = $this->getAllCustomIndexes();

        // Drop indexes
        $dropFlag = false;
        foreach ($indexesInDb as $indexId) {
            if (!array_key_exists($indexId, $indexesInModel)) {
                $this->dropIndex($connection, $this->quoteSchema($indexId));
                $dropFlag = true;
            }
        }
        if (!$dropFlag) {
            $this->output->writeln("<info>No index was dropped.</info>");
        }

        // Create indexes
        $createFlag = false;
        foreach ($indexesInModel as $key => $index) {
            if (!in_array($key, $indexesInDb)) {
                $this->createIndex($connection, $index);
                $createFlag = true;
            }
        }
        if (!$createFlag) {
            $this->output->writeln("<info>No index was created</info>");
        }

        return 0;
    }


    /**
     * @return array - array with custom indexes
     */
    protected function getAllCustomIndexes()
    {
        $connection = $this->em->getConnection();
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $currentSchema = CustomIndex::getCurrentSchema($connection);

        $result = [];

        $this->rememberAllAbstractWithIndex($metadata);

        // add all custom indexes into $result array
        $indexesToResult = function (
            ClassMetadata $meta,
            $tableName,
            $tablePostfix = false
        ) use (
            &$result,
            $currentSchema
        ) {
            if ($indexes = $this->readEntityIndexes($meta)) {
                foreach ($indexes as $aIndex) {
                    $schema = $meta->getSchemaName() ?: $currentSchema;

                    // skip index from side schema in single schema mode
                    if (!$this->searchInAllSchemas && $schema != $currentSchema) {
                        continue;
                    }

                    $index = new CustomIndex(
                        $tableName,
                        $aIndex->columns,
                        $aIndex->name . ($aIndex->name && $tablePostfix ? '_' . $tableName : ''),
                        $aIndex->unique,
                        $aIndex->using,
                        $aIndex->where,
                        $schema
                    );

                    $key = $schema.'.'.$index->getName();
                    $result[$key] = $index;
                }
            }
        };

        // create index from non abstract entity annotation
        foreach ($metadata as $meta) {
            if (!$this->isAbstract($meta)) {
                $indexesToResult(
                    $meta,
                    $meta->getTableName()
                );

                // create index using abstract parent
                $parentsMeta = $this->searchParentsWithIndex($meta);
                foreach ($parentsMeta as $parentMeta) {
                    if ($meta->inheritanceType === ClassMetadata::INHERITANCE_TYPE_JOINED) {
                        $tableName = $parentMeta->getTableName();
                    } else {
                        $tableName = $meta->getTableName();
                    }

                    $indexesToResult(
                        $parentMeta,
                        $tableName,
                        true
                    );
                }
            }
        }

        return $result;
    }

    /**
     * read entity annotation
     *
     * @param ClassMetadata $meta
     *
     * @return
     **/
    protected function readEntityIndexes(ClassMetadata $meta)
    {
        if (!isset($this->reader)) {
            $this->reader = new \Doctrine\Common\Annotations\AnnotationReader();
        }

        $refl = $meta->getReflectionClass();

        $annotation = $this->reader->getClassAnnotation($refl, CustomIndexes::class);
        if ($annotation) {
            return $annotation->indexes;
        }

        return null;
    }

    /**
     * Output validation error to console
     *
     * @param Exception $e
     **/
    protected function outputViolation(ConstraintViolation $v)
    {
        $this->output->writeln("<error>". $v->getMessage() ."</error>");
    }

    /**
     * Get available db indexes
     *
     * @param Connection $connection
     *
     * @return array - массив имен индексов
     **/
    protected function getCustomIndexesFromDb(Connection $connection)
    {
        return CustomIndex::getCurrentIndex(
            $connection,
            $this->searchInAllSchemas,
        );
    }

    /**
     * drop index
     *
     * @param Connection $connection
     * @param CustomIndex
     **/
    protected function dropIndex(Connection $connection, $indexId)
    {
        if ($this->input->getOption(self::DUMP_SQL_OPTION)) {
            $sql = CustomIndex::getDropIndexSql($connection->getDatabasePlatform()->getName(), $indexId);
            $this->output->writeln($sql.';');
            return;
        }

        CustomIndex::drop($connection, $indexId);
        $this->output->writeln("<info>Index ". $indexId ." was dropped.</info>");
    }

    /**
     * Create index
     *
     * @param Connection $connection
     * @param CustomIndex
     **/
    protected function createIndex(Connection $connection, CustomIndex $index)
    {
        $errors = $this->validator->validate($index);
        if (!count($errors)) {
            if ($this->input->getOption(self::DUMP_SQL_OPTION)) {
                $sql = $index->getCreateIndexSql($connection->getDatabasePlatform()->getName());
                $this->output->writeln($sql.';');
                return;
            }

            $index->create($connection);
            $this->output->writeln("<info>Index ". $index->getName() ." was created.</info>");

            return;
        }

        $this->output->writeln("<error>Index ". $index->getName() ." was not created.</error>");

        foreach ($errors as $error) {
            $this->outputViolation($error);
        }
    }

    /**
     * Check is abstract class and collect abstract classes
     *
     * @param ClassMetadata $meta
     *
     * @return bool - true if abstract, false otherwise
     **/
    protected function isAbstract(ClassMetadata $meta)
    {
        return $meta->getReflectionClass()->isAbstract();
    }

    /**
     * get array with names of abstract entity with custom index annotation
     *
     * @return bool
     **/
    protected function getAbstract()
    {
        return $this->abstractClasses;
    }

    /**
     * search and remember abstract entity with custom index annotation
     *
     * @param array $metadata
     **/
    protected function rememberAllAbstractWithIndex($metadata)
    {
        foreach ($metadata as $meta) {
            if ($this->isAbstract($meta)) {
                $this->abstractClasses[$meta->getName()] = $meta;
            }
        }
    }

    /**
     * get array with parent entity meta if parent has custom index annotation
     *
     * @param ClassMetadata $meta
     *
     * @return array
     **/
    protected function searchParentsWithIndex(ClassMetadata $meta)
    {
        $refl = $meta->getReflectionClass();
        $parentMeta = [];
        foreach ($this->getAbstract() as $entityName => $entityMeta) {
            if ($refl->isSubclassOf($entityName)) {
                $parentMeta[$entityName] = $entityMeta;
            }
        }

        return $parentMeta;
    }

    protected function quoteSchema($name)
    {
        $parts = explode('.', $name);
        $parts[0] = '"'.$parts[0].'"';
        return implode('.', $parts);
    }
}
