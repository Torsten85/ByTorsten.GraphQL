<?php
namespace ByTorsten\GraphQL\Service;

use Neos\Flow\Annotations as Flow;

use GraphQL\Type\Definition;
use GraphQL\Schema;
use GraphQL\Utils\BuildSchema;
use ByTorsten\GraphQL\Exception;

/**
 * @Flow\Scope("singleton")
 */
class SchemaBuilder
{
    const TAB = '    ';

    /**
     * @var Schema
     */
    protected $lastBuildSchema;

    /**
     * @param string $source
     * @param string $functionName
     * @return string
     */
    public function buildSchemaCode(string $source, string $functionName): string
    {
        $schema = BuildSchema::build($source);
        $this->lastBuildSchema = $schema;

        $code = [];
        $code[] = PHP_EOL;
        $code[] = 'function ' . $functionName . '() {';
        $code[] = $this->intent($this->generateCodeFromSchema($schema));
        $code[] = '}';

        return implode(PHP_EOL, $code);
    }

    /**
     * @param Schema $schema
     * @return string
     */
    public function generateCodeFromSchema(Schema $schema): string
    {
        $typeMap = $schema->getTypeMap();
        $types = array_filter(array_keys($typeMap), function (string $typename) {
            return $this->isDefinedType($typename);
        });
        sort($types);
        $types = array_map(function($typeName) use ($typeMap) {
            return $typeMap[$typeName];
        }, $types);

        $typesCodes = array_map(function (Definition\Type $type) {
            return "\$" . lcfirst($type->name) . " = " . $this->generateTypeCode($type) . ";";
        }, $types);

        $code = [];
        $code[] = implode(PHP_EOL, $typesCodes);
        $code[] = "return new " . get_class($schema) . "([";
        $code[] = $this->intent($this->generateSchemaDefinitionCode($schema, $types));
        $code[] = "]);";

        return implode(PHP_EOL, $code);
    }

    /**
     * @param string $str
     * @param int $count
     * @return string
     */
    protected function intent(string $str, int $count = 1)
    {
        $lines = explode(PHP_EOL, $str);
        $intentedLines = array_map(function (string $line) use ($count) {
            return str_repeat(self::TAB, $count) . $line;
        }, $lines);
        return implode(PHP_EOL, $intentedLines);
    }

    /**
     * @return Schema
     */
    public function getLastBuildSchema(): Schema
    {
        return $this->lastBuildSchema;
    }

    /**
     * @param Schema $schema
     * @param array $types
     * @return string
     */
    protected function generateSchemaDefinitionCode(Schema $schema, array $types): string
    {
        $definition = [];
        $queryType = $schema->getQueryType();
        if ($queryType) {
            $definition[] = "'query' => \$" . lcfirst($queryType->name);
        }

        $mutationType = $schema->getMutationType();
        if ($mutationType) {
            $definition[] = "'mutation' => \$" . lcfirst($mutationType->name);
        }

        $subscriptionType = $schema->getSubscriptionType();
        if ($subscriptionType) {
            $definition[] = "'subscription' => \$" . lcfirst($subscriptionType->name);
        }

        $filteredTypes = array_filter($types, function ($type) use ($queryType, $mutationType, $subscriptionType) {
           return !in_array($type, [$queryType, $mutationType, $subscriptionType]);
        });

        $definition[] = "'types' => [" .
            implode(', ',
                array_map(function (Definition\Type $type) use ($queryType) {
                    return '$' . lcfirst($type->name);
                }, $filteredTypes)
            ) . "]";

        return implode(',' . PHP_EOL, $definition);
    }

    /**
     * @param Definition\Type $type
     * @return string
     * @throws Exception
     */
    protected function generateTypeCode(Definition\Type $type): string
    {
        if ($type instanceof Definition\ScalarType) {
            return $this->generateScalarCode($type);
        } else if ($type instanceof Definition\ObjectType) {
            return $this->generateObjectCode($type);
        } else if ($type instanceof Definition\InterfaceType) {
            return $this->generateInterfaceCode($type);
        } else if ($type instanceof Definition\UnionType) {
            return $this->generateUnionCode($type);
        } else if ($type instanceof Definition\EnumType) {
            return $this->generateEnumCode($type);
        } else if ($type instanceof Definition\InputObjectType) {
            return $this->generateInputObjectCode($type);
        } else {
            throw new Exception(sprintf('Cannot generate code for type %s', get_class($type)));
        }
    }

    /**
     * @param Definition\ScalarType $scalarType
     * @return string
     */
    protected function generateScalarCode(Definition\ScalarType $scalarType): string
    {
        return $this->generateObjectCode($scalarType);
    }

    /**
     * @param Definition\Type $objectType
     * @return string
     */
    protected function generateObjectCode(Definition\Type $objectType): string
    {
        $configuration = [];
        $configuration[] = "'name' => '" . $objectType->name . "'";

        if ($objectType->description) {
            $configuration[] = "'description' => '" . $objectType->description . "'";
        }

        if (is_callable([$objectType, 'getInterfaces'])) {
            $interfaces = $objectType->getInterfaces();
            if (count($interfaces) > 0) {
                $interfaceNames = array_map(function ($interface) {
                    return '$' . lcfirst($interface->name);
                }, $interfaces);

                $configuration[] = implode(PHP_EOL, [
                    "'interfaces' => function () use (&" . implode(', &', $interfaceNames) . ') {',
                    $this->intent("return [" . implode(', ', $interfaceNames) . "];"),
                    '}'
                ]);
            }
        }

        if (is_callable([$objectType, 'getFields'])) {
            $configuration[] = "'fields' => [\n" .
                $this->intent(implode(',' . PHP_EOL, array_map(function ($fieldDefinition) use ($objectType) {
                    return "'" . $fieldDefinition->name . "' => [\n" . $this->intent($this->generateFieldCode($fieldDefinition, $objectType)) . "\n]";
                }, $objectType->getFields()))) . "\n]";
        }

        return "new " . get_class($objectType) . "([\n" . $this->intent(implode(',' . PHP_EOL, $configuration)) . "\n])";
    }

    /**
     * @param mixed $fieldDefinition
     * @param Definition\Type $parentType
     * @return string
     */
    protected function generateFieldCode($fieldDefinition, Definition\Type $parentType): string
    {
        $type = $fieldDefinition->getType();

        $propertiesCode = [];

        $getFieldType = function ($type) {
            if ($this->isDefinedType($type)) {
                return '$' . lcfirst($type->name);
            }

            return Definition\Type::class . '::' . lcfirst($type->name) . '()';
        };

        if ($type instanceof Definition\WrappingType) {
            $wrappedType = $type->getWrappedType();

            $wrapperName = $type instanceof Definition\ListOfType ? 'listOf' : 'nonNull';
            $typeCode = Definition\Type::class . '::' . $wrapperName . '(' . $getFieldType($wrappedType) . ')';

            if ($this->isDefinedType($wrappedType->name)) {
                $propertiesCode[] = implode(PHP_EOL, [
                    "'type' => function () use (&\$" . lcfirst($wrappedType->name) . ") {",
                    $this->intent("return " . $typeCode . ";"),
                    "}"
                ]);
            } else {
                $propertiesCode[] = "'type' => " . $typeCode;
            }
        } else {
            $typeCode = $getFieldType($type);

            if ($this->isDefinedType($type)) {
                $propertiesCode[] = implode(PHP_EOL, [
                    "'type' => function () use (&\$" . lcfirst($type->name) . ") {",
                    $this->intent("return \$" . lcfirst($type->name) . ";"),
                    "}"
                ]);
            } else {
                $propertiesCode[] = "'type' => " . $typeCode;
            }
        }


        if ($fieldDefinition->description) {
            $propertiesCode[] = "'description' => '" . $fieldDefinition->description . "'";
        }

        if (isset($fieldDefinition->args) && count ($fieldDefinition->args) > 0) {
            $argsCode = [];
            /** @var Definition\FieldArgument $fieldArgument */
            foreach($fieldDefinition->args as $fieldArgument) {
                $argsCode[] = "'" . $fieldArgument->name . "' => [\n" . $this->intent($this->generateFieldCode($fieldArgument, $type)) . "\n]";
            }

            $propertiesCode[] = "'args' => [\n" . $this->intent(implode(',' . PHP_EOL, $argsCode)) . "\n]";
        }

        return implode(',' . PHP_EOL, $propertiesCode);
    }

    /**
     * @param Definition\UnionType $unionType
     * @return string
     */
    protected function generateUnionCode(Definition\UnionType $unionType): string
    {
        $code = [];
        $code[] = "new " . Definition\UnionType::class . "([";
        $properties = [];
        $properties[] = "'name' => '" . $unionType->name . "'";

        $typeNames = array_map(function (Definition\Type $type) {
            return '$' . lcfirst($type->name);
        }, $unionType->getTypes());

        $types = [];
        $types[] = "'types' => function () use (&" . implode(', &', $typeNames) . ") {";
        $types[] = $this->intent('return [' . implode(', ', $typeNames) . '];');
        $types[] = "}";
        $properties[] = implode(PHP_EOL, $types);

        $code[] = $this->intent(implode(',' . PHP_EOL, $properties));
        $code[] = "])";

        return implode(PHP_EOL, $code);
    }

    /**
     * @param Definition\InterfaceType $interfaceType
     * @return string
     */
    protected function generateInterfaceCode(Definition\InterfaceType $interfaceType): string
    {
        return $this->generateObjectCode($interfaceType);
    }

    /**
     * @param Definition\EnumType $enumType
     * @return string
     */
    protected function generateEnumCode(Definition\EnumType $enumType): string
    {
        $code = [];
        $code[] = "new " . get_class($enumType) . "([";
        $code[] = $this->intent("'name' => '" . $enumType->name . "',");
        if ($enumType->description) {
            $code[] = $this->intent("'description' => '" . $enumType->description . "',");
        }
        $code[] = $this->intent("'values' => [");
        $valueCodes = [];
        /** @var Definition\EnumValueDefinition $value */
        foreach($enumType->getValues() as $value) {
            $valueCode = [];
            $valueCode[] = "'" . $value->name . "' => [";

            $propertyCode = [];
            $propertyCode[] = "'value' => '" . $value->value . "'";
            if ($value->description) {
                $propertyCode[] = "'description' => '" . $value->description . "'";
            }

            $valueCode[] = $this->intent(implode(',' . PHP_EOL, $propertyCode));
            $valueCode[] = "]";
            $valueCodes[] = implode(PHP_EOL, $valueCode);
        }
        $code[] = $this->intent(implode(',' . PHP_EOL, $valueCodes), 2);
        $code[] = $this->intent("]");
        $code[] = "])";

        return implode(PHP_EOL, $code);
    }

    /**
     * @param Definition\InputObjectType $inputObjectType
     * @return string
     */
    protected function generateInputObjectCode(Definition\InputObjectType $inputObjectType): string
    {
        return $this->generateObjectCode($inputObjectType);
    }

    /**
     * @param string $typename
     * @return bool
     */
    protected function isIntrospectionType(string $typename): bool
    {
        return strpos($typename, '__') === 0;
    }

    /**
     * @param string $typename
     * @return bool
     */
    protected function isBuiltInScalar(string $typename): bool
    {
        return (
            $typename === Definition\Type::STRING ||
            $typename === Definition\Type::BOOLEAN ||
            $typename === Definition\Type::INT ||
            $typename === Definition\Type::FLOAT ||
            $typename === Definition\Type::ID
        );
    }

    /**
     * @param string $typename
     * @return bool
     */
    protected function isDefinedType(string $typename): bool
    {
        return !$this->isIntrospectionType($typename) && !$this->isBuiltInScalar($typename);
    }
}