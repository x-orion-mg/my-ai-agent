<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Product;

final class LegrandProduct
{
    /**
     * @param array<string, string> $technicalData
     */
    public function __construct(
        public readonly string $reference,
        public readonly ?string $url,
        public readonly ?string $image,
        public readonly array $technicalData = [],
    ) {
    }

    /**
     * @return array{
     *     reference: string,
     *     url: ?string,
     *     image: ?string,
     *     technicalData: array<string,string>
     * }
     */
    public function toArray(): array
    {
        return [
            'reference' => $this->reference,
            'url' => $this->url,
            'image' => $this->image,
            'technicalData' => $this->technicalData,
        ];
    }
}