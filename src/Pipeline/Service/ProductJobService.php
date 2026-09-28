<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Service;

use MyAIAgent\Pipeline\Contract\JobHandlerInterface;
use MyAIAgent\Pipeline\DTO\ProductJob;
use MyAIAgent\Pipeline\Enum\ProductJobType;
use MyAIAgent\Pipeline\Exception\PermanentJobException;
use MyAIAgent\Pipeline\Exception\RetryableJobException;
use MyAIAgent\Pipeline\Repository\ProductJobRepository;
use Throwable;

final class ProductJobService
{
    /**
     * @param array<string,JobHandlerInterface> $handlers
     */
    public function __construct(
        private readonly ProductJobRepository $repository,
        private readonly RetryPolicy $retryPolicy,
        private readonly ProductJobScheduler $scheduler,
        private readonly array $handlers,
    ) {
    }

    public function run(int $jobId): void
    {
        print_r('eto a lelena');die();
        $job = $this->repository->find($jobId);

        if ($job === null || in_array(
            $job->status->value,
            ['completed', 'dead'],
            true
        )) {
            return;
        }

        $workerId = wp_generate_uuid4();
        $job = $this->repository->claim($jobId, $workerId);

        if ($job === null) {
            return;
        }

        $handler = $this->handlers[$job->type->value] ?? null;

        if (!$handler instanceof JobHandlerInterface) {
            $this->repository->fail(
                $job->id,
                'Aucun handler configuré pour le type ' . $job->type->value,
                true
            );
            return;
        }

        try {
            $data = $handler->handle($job);

            $this->repository->complete(
                $job->id,
                array_replace($job->payload, $data)
            );

            $this->scheduleNext($job, array_replace($job->payload, $data));
        } catch (RetryableJobException $exception) {
            if ($job->attempts >= $job->maxAttempts) {
                $this->repository->fail(
                    $job->id,
                    $exception->getMessage(),
                    true
                );
                return;
            }

            $delay = $this->retryPolicy->delay(
                $job->attempts,
                $exception->retryAfter
            );

            $this->repository->retry(
                $job->id,
                $delay,
                $exception->getMessage()
            );

            $this->scheduler->schedule(
                $job->id,
                $delay
            );
        } catch (PermanentJobException $exception) {
            $this->repository->fail(
                $job->id,
                $exception->getMessage(),
                true
            );
        } catch (Throwable $exception) {
            if ($job->attempts >= $job->maxAttempts) {
                $this->repository->fail(
                    $job->id,
                    $exception->getMessage(),
                    true
                );
                return;
            }

            $delay = $this->retryPolicy->delay($job->attempts);

            $this->repository->retry(
                $job->id,
                $delay,
                $exception->getMessage()
            );

            $this->scheduler->schedule(
                $job->id,
                $delay
            );
        }
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function scheduleNext(ProductJob $job, array $payload): void
    {
        $next = match ($job->type) {
            ProductJobType::SOURCE => ProductJobType::AI_GENERATION,
            ProductJobType::AI_GENERATION => ProductJobType::VALIDATION,
            ProductJobType::VALIDATION => ProductJobType::WOOCOMMERCE,
            ProductJobType::WOOCOMMERCE => null,
        };

        if ($next === null) {
            return;
        }

        $nextId = $this->repository->create(
            $job->importRowId,
            $job->reference,
            $next,
            $payload
        );

        if ($nextId > 0) {
            $this->scheduler->schedule($nextId, 0);
        }
    }
}
