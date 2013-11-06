<?php

namespace SilverStripe\Behat\Tester;

use Zodyac\Behat\PerceptualDiffExtension\Tester as BaseStepTester;

/**
 * Overloaded to allow ajax steps to complete before taking screenshot.
 * Ajax handlers are defined via SilverStripe\BehatExtension\Context\BasicContext.
 * If that context isn't used, the behaviour is the same as the parent implementation.
 */
class StepTester extends BaseStepTester
{
    
    /**
     * Executes provided step definition.
     *
     * If the result is not a failure then take a screenshot and compare for differences.
     *
     * @param StepNode $step
     * @param DefinitionInterface $definition
     * @throws PerceptualDiffException If there are differences compared to the baseline
     */
    protected function executeStepDefinition(StepNode $step, DefinitionInterface $definition)
    {
        parent::executeStepDefinition($step, $definition);

        // Wait for an ajax request to complete, but only for a maximum of X seconds to avoid deadlocks
        $session = $this->context->getSession();
        $this->getSession()->wait(5000,
            "(typeof window.__ajaxStatus !== 'undefined' ? window.__ajaxStatus() : 'no ajax') !== 'waiting'"
        );

        $diff = $this->screenshotComparator->takeScreenshot($this->context, $step);
        if ($diff > 0 && $this->failOnDiff) {
            // There were differences between the two screenshots
            throw new PerceptualDiffException(sprintf('There was a perceptual difference of %d', $diff));
        }
    }
}
