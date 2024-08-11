<?php

use Intaro\CustomIndexBundle\Command\IndexUpdateCommand;
use Intaro\CustomIndexBundle\Validator\Constraints\AllowedIndexTypeValidator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services
        ->set('intaro_custom_index.allowed_index_type_validator', AllowedIndexTypeValidator::class)
        ->arg('$allowedIndexTypes', '%intaro.custom.index.allowed_index_types%')
        ->tag('validator.constraint_validator')

        ->set('intaro_custom_index.command.index_update_command', IndexUpdateCommand::class)
        ->arg('$searchInAllSchemas', '%intaro.custom_index.search_in_all_schemas%')
    ;
};
