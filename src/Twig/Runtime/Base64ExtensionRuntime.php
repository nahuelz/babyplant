<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class Base64ExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
        // Inject dependencies if needed
    }

    public function doSomething($value)
    {
        // ...
    }
}
