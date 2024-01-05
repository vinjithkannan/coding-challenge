<?php

namespace App\Service\Interfaces;

interface ParseJsonLinesInterface
{
    public function delineFromfile(string $filepath): array;
    public function delineEachLineFromFile(string $filepath): array;
}
