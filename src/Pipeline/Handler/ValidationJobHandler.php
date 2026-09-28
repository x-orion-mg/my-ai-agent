<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Handler;

use MyAIAgent\Agent\Agents\Product\Response\ProductResponseValidator;
use MyAIAgent\Agent\Response\AiResponseParser;
use MyAIAgent\Pipeline\Contract\JobHandlerInterface;
use MyAIAgent\Pipeline\DTO\ProductJob;
use MyAIAgent\Pipeline\Exception\PermanentJobException;
use Throwable;

final class ValidationJobHandler implements JobHandlerInterface
{
    public function __construct(
        private readonly AiResponseParser $parser,
        private readonly ProductResponseValidator $validator,
    ) {
    }

    public function handle(ProductJob $job): array
    {
        $text = $job->payload['ai']['text'] ?? null;

        if (!is_string($text) || trim($text) === '') {
            throw new PermanentJobException(
                'La réponse IA est absente.'
            );
        }

        try {
            $data = $this->parser->parse($text);

            /*
             * Le validateur actuel du projet attend des chaînes pour
             * sku/ean et pour schema.gtin. On complète uniquement les
             * identifiants absents avec les données déjà importées.
             */
            $row = $job->payload['import_row'] ?? [];

            if (!isset($data['sku']) || $data['sku'] === null || trim((string) $data['sku']) === '') {
                $data['sku'] = $job->reference;
            }

            if (!isset($data['ean']) || $data['ean'] === null) {
                $data['ean'] = is_array($row) ? (string) ($row['ean'] ?? '') : '';
            }

            if (!isset($data['brand']) || $data['brand'] === null || trim((string) $data['brand']) === '') {
                $data['brand'] = 'Legrand';
            }

            if (!isset($data['schema']) || !is_array($data['schema'])) {
                throw new \RuntimeException('Le champ schema est absent.');
            }

            if (!isset($data['schema']['sku']) || $data['schema']['sku'] === null) {
                $data['schema']['sku'] = $data['sku'];
            }

            if (!isset($data['schema']['gtin']) || $data['schema']['gtin'] === null) {
                $data['schema']['gtin'] = $data['ean'];
            }

            if (!isset($data['schema']['brand']) || !is_array($data['schema']['brand'])) {
                $data['schema']['brand'] = [
                    '@type' => 'Brand',
                    'name' => 'Legrand',
                ];
            }

            $this->validator->validate($data);
        } catch (Throwable $exception) {
            throw new PermanentJobException(
                'La réponse IA est invalide : ' . $exception->getMessage(),
                previous: $exception
            );
        }
        return [
            'validated' => true,
            'data' => $data,
        ];
    }
}
