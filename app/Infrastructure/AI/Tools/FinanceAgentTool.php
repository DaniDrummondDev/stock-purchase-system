<?php

declare(strict_types=1);

namespace App\Infrastructure\AI\Tools;

use App\Domain\AI\Contracts\AgentContext;
use App\Domain\AI\Contracts\FinanceAgentInterface;
use App\Domain\AI\Contracts\TriggerType;
use App\Infrastructure\AI\Safety\SafeAgentExecutor;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\StringType;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FinanceAgentTool implements Tool
{
    public function __construct(
        private readonly FinanceAgentInterface $agent,
        private readonly SafeAgentExecutor $executor,
        private readonly string $clienteId,
        private readonly TriggerType $triggerType = TriggerType::Chat,
    ) {}

    public function name(): string
    {
        return $this->agent->getName();
    }

    public function description(): Stringable|string
    {
        return $this->agent->getDescription();
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $parameterSchema = $this->agent->getParameterSchema();

        return $this->convertJsonSchemaToTypes($parameterSchema);
    }

    public function handle(Request $request): Stringable|string
    {
        $context = new AgentContext(
            clienteId: $this->clienteId,
            request: json_encode($request->all()) ?: '{}',
            triggerType: $this->triggerType,
            additionalParams: $request->all(),
        );

        $result = $this->executor->execute($this->agent, $context);

        return json_encode([
            'data' => $result->data,
            'summary' => $result->summary,
            'confidence' => $result->confidence,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Convert a JSON Schema properties array to Laravel JsonSchema Type instances.
     *
     * @return array<string, Type>
     */
    private function convertJsonSchemaToTypes(array $schema): array
    {
        $properties = $schema['properties'] ?? [];
        $required = $schema['required'] ?? [];
        $types = [];

        foreach ($properties as $name => $definition) {
            $type = $this->createTypeFromDefinition($definition);

            if (in_array($name, $required, true)) {
                $type->required();
            }

            if (isset($definition['description'])) {
                $type->description($definition['description']);
            }

            $types[$name] = $type;
        }

        return $types;
    }

    private function createTypeFromDefinition(array $definition): Type
    {
        $type = $definition['type'] ?? 'string';

        return match ($type) {
            'string' => $this->createStringType($definition),
            'integer' => new IntegerType,
            'number' => new NumberType,
            'boolean' => new BooleanType,
            'array' => new ArrayType,
            default => new StringType,
        };
    }

    private function createStringType(array $definition): StringType
    {
        $type = new StringType;

        if (isset($definition['enum'])) {
            $type->enum($definition['enum']);
        }

        return $type;
    }
}
