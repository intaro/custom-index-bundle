<?php

use Intaro\CustomIndexBundle\Command\IndexUpdateCommand;
use Intaro\CustomIndexBundle\Metadata\Annotation\Reader;
use Intaro\CustomIndexBundle\DBAL\QueryExecutor;
use Intaro\CustomIndexBundle\Validator\Constraints\AllowedIndexTypeValidator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services
        ->set('intaro_custom_index.allowed_index_type_validator', AllowedIndexTypeValidator::class)
        ->arg('$allowedIndexTypes', '%intaro.custom.index.allowed_index_types%')
        ->tag('validator.constraint_validator')

        ->set('intaro_custom_index.index_annotation_reader', Reader::class)

        ->set('intaro_custom_index.query_executor', QueryExecutor::class)

        ->set('intaro_custom_index.command.index_update_command', IndexUpdateCommand::class)
        ->arg('$reader', service('intaro_custom_index.index_annotation_reader'))
        ->arg('$queryExecutor', service('intaro_custom_index.query_executor'))
        ->arg('$searchInAllSchemas', '%intaro.custom_index.search_in_all_schemas%')
    ;
};
