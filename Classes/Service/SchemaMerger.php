<?php
namespace ByTorsten\GraphQL\Service;

class SchemaMerger {

    const INPUT_TYPE_PATTERN = '/input ([\s\S]*?) {/';
    const ENUM_TYPE_PATTERN = '/enum ([\s\S]*?) {/';
    const SCALAR_TYPE_PATTERN = '/scalar ([\s\S]*?).*/i';
    const INTERFACE_TYPE_PATTERN = '/interface ([\s\S]*?) {/';
    const UNION_TYPE_PATTERN = '/union ([\s\S]*?) =/';
    const CUSTOM_TYPE_PATTERN = '/type (?!Query)(?!Mutation)(?!Subscription)([\s\S]*?) {/';

    /**
     * @param array $types
     * @param string $typeName
     * @return string
     */
    protected static function sliceDefaultTypes(array $types, string $typeName): string
    {
        $pattern = sprintf('/type +%s *{(.*?)}/im', $typeName);

        $extratedTypes = array_map(function ($type) use ($pattern) {
            $matches = [];
            preg_match_all($pattern, preg_replace('/(\s)+/im', ' ', $type), $matches);
            return trim($matches[1][0] ?? '');
        }, $types);

        return implode(' ', $extratedTypes);
    }

    /**
     * @param array $types
     * @param string $pattern
     * @param bool $scalar
     * @param string $closingChar
     * @return array
     */
    protected static function sliceTypes(array $types, string $pattern, bool $scalar = false, string $closingChar = '}'): array
    {
        $extractedMatches = [];
        foreach ($types as $type) {
            $matches = [];
            preg_match_all($pattern, $type, $matches);
            foreach($matches[0] as $match) {
                if ($scalar) {
                    $extractedMatches[] = $match;
                } else {
                    $startIndex = strpos($type, $match);
                    $endIndex = strpos($type, $closingChar, $startIndex);
                    $extractedMatches[] = substr($type, $startIndex, $endIndex - $startIndex + 1);
                }
            }
            
        }

        return array_filter($extractedMatches, function ($match) {
            return $match;
        });
    }

    /**
     * @param $value
     * @return bool
     */
    protected static function notEmpty(string $value): bool
    {
        return preg_replace('/(\s)/im', '', $value) !== '';
    }

    /**
     * @param array $types
     * @return string
     */
    public static function merge(array $types): string
    {
        if (count($types) === 1) {
            return $types[0];
        }

        $inputTypes = self::sliceTypes($types, self::INPUT_TYPE_PATTERN);
        $enumTypes = self::sliceTypes($types, self::ENUM_TYPE_PATTERN);
        $scalarTypes = self::sliceTypes($types, self::SCALAR_TYPE_PATTERN, true);
        $interfaceTypes = self::sliceTypes($types, self::INTERFACE_TYPE_PATTERN);
        $unionTypes = self::sliceTypes($types, self::UNION_TYPE_PATTERN, false, '\n');
        $customTypes = self::sliceTypes($types, self::CUSTOM_TYPE_PATTERN);
        $queryTypes = self::sliceDefaultTypes($types, 'Query');
        $mutationTypes = self::sliceDefaultTypes($types, 'Mutation');
        $subscriptionTypes = self::sliceDefaultTypes($types, 'Subscriptions');

        $schema = [
            'schema {',
            'query: Query'
        ];

        if (self::notEmpty($mutationTypes)) {
            $schema[] = 'mutation: Mutation';
        }

        if (self::notEmpty($subscriptionTypes)) {
            $schema[] = 'subscription: Subscription';
        }

        $schema[] = '}';

        if (self::notEmpty($queryTypes)) {
            $schema[] = sprintf('type Query { %s }', $queryTypes);
        }

        if (self::notEmpty($mutationTypes)) {
            $schema[] = sprintf('type Mutation { %s }', $mutationTypes);
        }

        if (self::notEmpty($subscriptionTypes)) {
            $schema[] = sprintf('type Subscription { %s }', $subscriptionTypes);
        }

        $mergedTypes = [];
        $allTypes = [$inputTypes, $enumTypes, $scalarTypes, $interfaceTypes, $unionTypes, $customTypes];

        foreach($allTypes as $type) {
            if (count($type) > 0) {
                $mergedTypes = array_merge($mergedTypes, $type);
            }
        }

        return implode($schema, PHP_EOL) . PHP_EOL . implode($mergedTypes, PHP_EOL);
    }
}