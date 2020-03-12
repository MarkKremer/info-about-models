<?php

require __DIR__.'/vendor/autoload.php';

return (new MattAllan\LaravelCodeStyle\Config())
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in('src')
            ->in('tests')
    )
    ->setRules([
        '@Laravel' => true,
    ]);
