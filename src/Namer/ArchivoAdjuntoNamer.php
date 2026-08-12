<?php

namespace App\Namer;

use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Vich\UploaderBundle\Naming\NamerInterface;
use Vich\UploaderBundle\Naming\Polyfill\FileExtensionTrait;

class ArchivoAdjuntoNamer implements NamerInterface, DirectoryNamerInterface
{
    use FileExtensionTrait;

    public function name($object, PropertyMapping $mapping): string
    {
        $file = $mapping->getFile($object);
        $name = str_replace('.', '', uniqid('', true));
        $extension = $this->getExtension($file);

        return $extension ? sprintf('%s.%s', $name, $extension) : $name;
    }

    public function directoryName($object, PropertyMapping $mapping): string
    {
        if (method_exists($object, 'getCustomPath') && $object->getCustomPath()) {
            return $object->getCustomPath();
        }

        return '';
    }
}
