<?php

/*
 * PHP-CS-Fixer-Konfiguration für phlorder (TYPO3 Coding Standards).
 * Nur in separatem Commit anwenden — der Bestandscode nutzt Tabs und
 * deutsche Kommentare, ein Fixer-Lauf im Feature-Commit macht das Diff unlesbar.
 */

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->getFinder()
    ->in(__DIR__ . '/Classes')
    ->in(__DIR__ . '/Tests')
    ->in(__DIR__ . '/Configuration');

return $config;
