<?php
namespace Intaro\CustomIndexBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\ConstraintViolation;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Connection;

use Intaro\CustomIndexBundle\DBAL\Schema\CustomIndex;

class IndexUpdateCommand extends Command
{
    const CUSTOM_INDEXES_ANNOTATION
        = 'Intaro\\CustomIndexBundle\\Annotations\\CustomIndexes';

    const DUMPSQL = 'dump-sql';

    // array with abstract classes
    protected $abstractClasses = [];

    //InputInterface
    protected $input;

    //OutputInterface
    protected $output;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('intaro:doctrine:index:update')
            ->addOption(self::DUMPSQL, null, InputOption::VALUE_NONE, 'Dump sql instead creating index')
            ->setDescription('Create new and drop not existing custom indexes');
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $kernel             = $this->getApplication()->getKernel();
        $container          = $kernel->getContainer();
        $em                 = $container->get('doctrine')->getManager();
        $this->validator    = $container->get('validator');
        $connection         = $em->getConnection();

        $indexesInDb = $this->getCustomIndexesFromDb($connection);
        $indexesInModel = $this->getAllCustomIndexes($em);

        // Drop indexes
        $dropFlag = false;
        foreach ($indexesInDb as $indexId) {
            if (!array_key_exists($indexId, $indexesInModel)) {
                $this->dropIndex($connection, $indexId);
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
    }


    /**
     * get custom indexes from all entities
     *
     * @param EntityManager $em
     *
     * @return array - array with custom indexes
    **/
    protected function getAllCustomIndexes(EntityManager $em)
    {
        $metadata = $em->getMetadataFactory()
            ->getAllMetadata();

        $result = [];

        $this->rememberAllAbstractWithIndex($metadata);

        // add all custom indexes into $result array
        $indexesToResult = function (ClassMetadata $meta, $tableName, $tablePostfix = false) use (&$result) {
            if ($indexes = $this->readEntityIndexes($meta)) {
                foreach ($indexes as $aIndex) {
                    $index = new CustomIndex(
                        $tableName,
                        $aIndex->columns,
                        $aIndex->name . ($aIndex->name && $tablePostfix ? '_' . $tableName : ''),
                        $aIndex->unique,
                        $aIndex->using,
                        $aIndex->where,
                        $meta->getSchemaName()
                    );
                    $result[$index->getName(true)] = $index;
                }
            }
        };

        // create index from non abstract entity annotation
        foreach ($metadata as $meta) {
            if (!$this->isAbstract($meta)) {
                $indexesToResult($meta, $meta->getTableName());

                // create index using abstract parent
                $parentsMeta = $this->searchParentsWithIndex($meta);
                foreach ($parentsMeta as $parentMeta) {
                    $indexesToResult($parentMeta, $meta->getTableName(), true);
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

        $annotation = $this->reader->getClassAnnotation($refl, self::CUSTOM_INDEXES_ANNOTATION);
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
        $result = [];

        $st = $connection->prepare("
            SELECT schemaname || '.' || indexname as relname FROM pg_indexes
            WHERE indexname LIKE ?
        ");
        $st->execute([CustomIndex::PREFIX . '%']);

        if ($data = $st->fetchAll()) {
            foreach ($data as $row) {
                $result[] = $row['relname'];
            }
        }

        return $result;
    }

    /**
     * drop index
     *
     * @param Connection $connection
     * @param CustomIndex
    **/
    protected function dropIndex(Connection $connection, $indexId)
    {
        if ($this->input->getOption(self::DUMPSQL)) {
            $sql = CustomIndex::getDropIndexSql($connection->getDatabasePlatform()->getName(), $indexId);
            $this->output->writeln($sql.';');
            return;
        }

        $result = CustomIndex::drop($connection, $indexId);

        if ($result) {
            $this->output->writeln("<info>Index ". $indexId ." was dropped.</info>");
        } else {
            $this->output->writeln("<error>Index ". $indexId ." was not dropped.</error>");
        }

        return $result;
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
            if ($this->input->getOption(self::DUMPSQL)) {
                $sql = $index->getCreateIndexSql($connection->getDatabasePlatform()->getName());
                $this->output->writeln($sql.';');
                return;
            }

            $result = $index->create($connection);

            $this->output->writeln("<info>Index ". $index->getName() ." was created.</info>");

            return $result;
        } else {
            $this->output->writeln("<error>Index ". $index->getName() ." was not created.</error>");

            foreach ($errors as $error) {
                $this->outputViolation($error);
            }
        }

        return false;
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
}
