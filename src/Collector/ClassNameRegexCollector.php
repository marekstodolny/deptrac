<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Collector;

use LogicException;
use Qossmic\Deptrac\AstRunner\AstMap;
use Qossmic\Deptrac\AstRunner\AstMap\AstClassReference;

class ClassNameRegexCollector extends RegexCollector implements CollectorInterface
{
    public function getType(): string
    {
        return 'classNameRegex';
    }

    public function satisfy(
        array $configuration,
        AstMap\AstTokenReference $astTokenReference,
        AstMap $astMap,
        Registry $collectorRegistry,
        array $resolutionTable = []
    ): bool {
        if (!$astTokenReference instanceof AstClassReference) {
            return false;
        }

        return $astTokenReference->getTokenName()
            ->match($this->getValidatedPattern($configuration));
    }

    protected function getPattern(array $configuration): string
    {
        if (!isset($configuration['regex']) || !is_string($configuration['regex'])) {
            throw new LogicException('ClassNameRegexCollector needs the regex configuration.');
        }

        return $configuration['regex'];
    }
}
