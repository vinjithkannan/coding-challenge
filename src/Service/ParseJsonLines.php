<?php

namespace App\Service;

use App\Service\Interfaces\ParseJsonLinesInterface;
use Rs\JsonLines\JsonLines;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ParseJsonLines implements ParseJsonLinesInterface
{

    private JsonLines $rsJsonLine;
    private LoggerInterface $logger;

    public function __construct(JsonLines $rsJsonLine, LoggerInterface $logger)
    {
        $this->rsJsonLine = $rsJsonLine;
        $this->logger = $logger;
    }

    public function delineFromFile(string $filepath): array
    {
        try {
            $fileContent = $this->rsJsonLine->delineFromfile($filepath);
            if([] === (array) $fileContent) {
                throw new NotFoundHttpException('File not found or empty file');
            }

            return json_decode($fileContent, true);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());

            return [
                'error' => [
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                ]
            ];
        }
    }

    public function delineEachLineFromFile(string $filepath): array
    {
        try {
            $fileContent = $this->rsJsonLine->delineEachLineFromFile($filepath);
            if([] === (array) $fileContent || null == $fileContent) {
                throw new NotFoundHttpException('File not found or empty file');
            }

            return json_decode($fileContent, true);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());

            return [
                'error' => [
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                ]
            ];
        }
    }
}
