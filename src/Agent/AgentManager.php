<?php


declare(strict_types=1);

namespace MyAIAgent\Agent;

use InvalidArgumentException;

final class AgentManager
{
    /**
     * @var array<string, AgentInterface>
     */
    private array $agents = [];

    public function register(AgentInterface $agent): void
    {
        $id = $agent->id();

        if (isset($this->agents[$id])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Agent "%s" is already registered.',
                    $id
                )
            );
        }

        $this->agents[$id] = $agent;
    }

    public function has(string $id): bool
    {
        return isset($this->agents[$id]);
    }

    public function get(string $id): AgentInterface
    {
        if (!$this->has($id)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Agent "%s" not found.',
                    $id
                )
            );
        }

        return $this->agents[$id];
    }

    /**
     * @return array<string, AgentInterface>
     */
    public function all(): array
    {
        return $this->agents;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function execute(
        string       $agentId,
        AgentContext $context,
        array        $input
    ): AgentResult
    {
        $agent = $this->get($agentId);

        $agent->validate($input);

        return $agent->execute($context);
    }
}
