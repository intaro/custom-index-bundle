<?php

namespace Intaro\CustomIndexBundle\DBAL\Schema;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContext;

use Doctrine\DBAL\Connection;

class CustomIndex
{
    const PREFIX = 'i_cindex_';
    
    const UNIQUE = 'unique';

    protected $availableUsing = ['btree', 'hash', 'gin', 'gist'];

    protected $name;
    
    protected $columns = [];
    
    protected $unique;
    
    protected $using;
    
    protected $tableName;
 
    /**
     * validation
     *
     * @param ClassMetadata
    **/
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('tableName', new Assert\Length([
            'min'           => 1,
            'minMessage'    => 'TableName must be set',
            'max'           => 63,
            'maxMessage'    => 'TableName is too long',
        ]));
        $metadata->addPropertyConstraint('name', new Assert\Length([
            'min'           => 1,
            'minMessage'    => 'Name must be set',
            'max'           => 63,
            'maxMessage'    => 'Name is too long',
        ]));
        $metadata->addConstraint(new Assert\Callback([
            'methods' => ['isColumnsValid'],
        ]));
    }

    /**
     * drop index
     *
     * @param Connection $con
     *
     * @return bool
    **/
    public static function drop(Connection $con, $indexId)
    {
        $platform = $con->getDatabasePlatform()
            ->getName();
        
        $sql = self::getDropIndexSql($platform, $indexId);
        
        $st = $con->prepare($sql);
        return $st->execute();
    }

    /**
     * sql for index delete
     *
     * @param $platform
     * @param $indexName - index name in db
     *
     * @return string - sql
    **/
    public static function getDropIndexSql($platform, $indexName)
    {
        $sql = '';
        switch($platform) {
            case 'postgresql':
                $sql = 'DROP INDEX ' . $indexName;
                break;
            default:
                throw new \LogicException("Platform {$platform} does not support");
        }
        return $sql;
    }

    public function __construct($tableName, $columns, $name = '', $unique = false, $using = '')
    {
        $vars = ['tableName', 'columns', 'unique', 'using'];
        foreach($vars as $var) {
            $method = 'set' . ucfirst($var);
            $this->$method($$var);
        }
        
        if($name) {
            $this->setName($name);
        } else {
            $this->generateName();
        }
    }

    /**
     * check columns field
     *
     * @param ExecutionContext $context
    **/
    public function isColumnsValid(ExecutionContext $context)
    {
        foreach($this->getColumns() as $col) {
            if(!is_string($col)) {
                $context->addViolationAtSubPath('columns', 'Columns must be string');                
            }
        }
    }

    /**
     * create index
     *
     * @param Connection $con
     *
     * @return bool
    **/
    public function create(Connection $con)
    {
        $platform = $con->getDatabasePlatform()
            ->getName();
        
        $sql = $this->getCreateIndexSql($platform);
        $st = $con->prepare($sql);
        $result = $st->execute();
        
        return $result;
    }
    
    /**
     * sql fo index create
     *
     * @param string $platform
     *
     * @return string - sql
    **/
    public function getCreateIndexSql($platform)
    {
        $sql = '';
        switch($platform) {
            case 'postgresql':
                $sql = 'CREATE ';
                if($this->getUnique())
                    $sql .= 'UNIQUE ';
                $sql .= 'INDEX ' .  $this->getName() . ' ';
                $sql .= 'ON ' . $this->getTableName() . ' ';
                if($this->getUsing())
                    $sql .= 'USING ' . $this->getUsing() . ' ';
                $sql .= '(';
                
                $columns = $this->getColumns();
                $counter = 0;
                foreach($columns as $col) {
                    $counter ++;
                    $sql .= $col;
                    if($counter != count($columns))
                        $sql .= ', ';
                }
                $sql .= ')';
                break;
            default:
                throw new \LogicException("Platform {$platform} does not support");
        }
        
        return $sql; 
    }
        
    /**
     * generate index name
     *
     * @return string - index name
    **/
    public function generateName()
    {
        $columns = $this->getColumns();
        $strToMd5 = $this->getTableName();

        foreach($columns as $column) {
            $strToMd5 .= $column;
        }

        $strToMd5 .= $this->getUsing();
        $name = self::PREFIX 
            . ( $this->getUnique() ? self::UNIQUE . '_' : '' ) 
            . md5($strToMd5);
        
        $this->setName($name);
        
        return $name;
    }
    
    public function setTableName($tableName)
    {
    	$this->tableName = $tableName;
    	return $this;
    }
    
    public function getTableName()
    {
    	return $this->tableName;
    }
    
    public function setName($name)
    {
        if(strpos($name, self::PREFIX) !== 0) {
            $name = self::PREFIX . $name;
        }
    
    	$this->name = $name;
    	return $this;
    }
    
    public function getName()
    {
    	return $this->name;
    }
    
    public function setColumns($columns)
    {
        if(is_string($columns))
            $columns = [$columns];
    
    	$this->columns = $columns;
    	return $this;
    }
    
    public function getColumns()
    {
    	return $this->columns;
    }
    
    public function setUnique($unique)
    {
    	$this->unique = (bool) $unique;
    	return $this;
    }
    
    public function getUnique()
    {
    	return $this->unique;
    }
    
    public function setUsing($using)
    {
        if($using && !in_array($using, $this->availableUsing)) {
            throw new \InvalidArgumentException(
                'Can\'t create index for table '. $this->getTableName() 
                    .'. Using must be one of this value: ' . implode(', ', $this->availableUsing)
            );
        }
    
    	$this->using = $using;
    	return $this;
    }
    
    public function getUsing()
    {
    	return $this->using;
    }
}