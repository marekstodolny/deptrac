<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\OutputFormatter;

use DOMAttr;
use DOMDocument;
use DOMElement;
use Exception;
use Qossmic\Deptrac\Console\Output;
use Qossmic\Deptrac\RulesetEngine\Context;
use Qossmic\Deptrac\RulesetEngine\SkippedViolation;
use Qossmic\Deptrac\RulesetEngine\Violation;

final class XMLOutputFormatter implements OutputFormatterInterface
{
    private const DEFAULT_PATH = './deptrac-report.xml';

    public static function getName(): string
    {
        return 'xml';
    }

    public static function getConfigName(): string
    {
        return self::getName();
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function finish(
        Context $context,
        Output $output,
        OutputFormatterInput $outputFormatterInput
    ): void {
        $xml = $this->createXml($context);

        $dumpXmlPath = $outputFormatterInput->getOutputPath() ?? self::DEFAULT_PATH;
        file_put_contents($dumpXmlPath, $xml);
        $output->writeLineFormatted('<info>XML Report dumped to '.realpath($dumpXmlPath).'</info>');
    }

    /**
     * @throws Exception
     */
    private function createXml(Context $dependencyContext): string
    {
        if (!class_exists(DOMDocument::class)) {
            throw new Exception('Unable to create xml file (php-xml needs to be installed)');
        }

        $xmlDoc = new DOMDocument('1.0', 'UTF-8');
        $xmlDoc->formatOutput = true;

        $rootEntry = $xmlDoc->createElement('entries');

        foreach ($dependencyContext->violations() as $rule) {
            $this->addRule('violation', $rootEntry, $xmlDoc, $rule);
        }

        foreach ($dependencyContext->skippedViolations() as $rule) {
            $this->addRule('skipped_violation', $rootEntry, $xmlDoc, $rule);
        }

        $xmlDoc->appendChild($rootEntry);

        return (string) $xmlDoc->saveXML();
    }

    /**
     * @param Violation|SkippedViolation $rule
     */
    private function addRule(string $type, DOMElement $rootEntry, DOMDocument $xmlDoc, $rule): void
    {
        $entry = $xmlDoc->createElement('entry');
        $entry->appendChild(new DOMAttr('type', $type));

        $entry->appendChild($xmlDoc->createElement('LayerA', $rule->getDependantLayerName()));
        $entry->appendChild($xmlDoc->createElement('LayerB', $rule->getDependeeLayerName()));

        $dependency = $rule->getDependency();
        $entry->appendChild($xmlDoc->createElement('ClassA', $dependency->getDependant()->toString()));
        $entry->appendChild($xmlDoc->createElement('ClassB', $dependency->getDependee()->toString()));

        $fileOccurrence = $dependency->getFileOccurrence();
        $occurrence = $xmlDoc->createElement('occurrence');
        $occurrence->setAttribute('file', $fileOccurrence->getFilepath());
        $occurrence->setAttribute('line', (string) $fileOccurrence->getLine());
        $entry->appendChild($occurrence);

        $rootEntry->appendChild($entry);
    }
}
