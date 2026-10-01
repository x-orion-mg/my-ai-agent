<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Product;

final readonly class LegrandProduct
{
    /**
     * @param array<string, string> $technicalData
     */
    public function __construct(
        public string  $reference,
        public ?string $url,
        public ?string $image,
        public ?string $technicalData ,
    ) {
    }

    /**
     * @return array{
     *     reference: string,
     *     url: ?string,
     *     image: ?string,
     *     technicalData: ?string
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