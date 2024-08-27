## New v1.0 version package

### Major changes:
<ul>
    <li>Minimum php version raised to 8.1</li>
    <li>Removed support for symfony < 5</li>
    <li>Now you must always pass an array in a property `columns` (previously it was possible to pass a string/array)</li>
    <li>Replaced the use of annotations with attributes</li>
</ul>

### Automatic transition with Rector

You may use rector to automatically convert your code to the new version.

```php
<?php

use Intaro\CustomIndexBundle\Annotations\CustomIndexes;
use Intaro\CustomIndexBundle\Metadata\Attribute\CustomIndex;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Property\NestedAnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationPropertyToAttributeClass;
use Rector\Php80\ValueObject\NestedAnnotationToAttribute;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths(['src']);

    $rectorConfig->ruleWithConfiguration(NestedAnnotationToAttributeRector::class, [
        new NestedAnnotationToAttribute(CustomIndexes::class, [
            new AnnotationPropertyToAttributeClass(CustomIndex::class, 'indexes'),
        ], true),
    ]);
};
```
