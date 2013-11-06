<?php

namespace SilverStripe\Behat\PerceptualDiffExtension\Tester;

use Behat\Behat\Definition\DefinitionInterface;
use Behat\Behat\Context\ContextInterface;
use Behat\Behat\Tester\StepTester as BaseStepTester;
use Behat\Gherkin\Node\StepNode;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zodyac\Behat\PerceptualDiffExtension\Exception\PerceptualDiffException;
use Zodyac\Behat\PerceptualDiffExtension\Comparator\ScreenshotComparator;

/**
 * Overloaded to allow ajax steps to complete before taking screenshot.
 * Ajax handlers are defined via SilverStripe\BehatExtension\Context\BasicContext.
 * If that context isn't used, the behaviour is the same as the parent implementation.
 *
 * This class is only necessary because the StepTester takes the screenshot before
 * the "AfterStep" event hook, where SilverStripe's BehatExtension usually waits for
 * ajax requests to complete (too late). Behat itself doesn't provide event hooks
 * for the step tester execution chain.
 */
class StepTester extends BaseStepTester
{
    /**
     * The screenshot comparator
     *
     * @var ScreenshotComparator
     */
    protected $screenshotComparator;

    /**
     * Whether to fail the step if there are perceptual differences.
     *
     * @var boolean
     */
    protected $failOnDiff;

    /**
     * The run context.
     *
     * Redefined as protected to work around the private variable
     * in the base class.
     *
     * @var ContextInterface
     */
    protected $context;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->screenshotComparator = $container->get('behat.perceptual_diff_extension.comparator.screenshot');
        $this->failOnDiff = $container->getParameter('behat.perceptual_diff_extension.fail_on_diff');
    }

    /**
     * Sets run context.
     *
     * @param ContextInterface $context
     */
    public function setContext(ContextInterface $context)
    {
        // Must set the parent context too
        parent::setContext($context);
        $this->context = $context;
    }

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

        $session = $this->context->getSession();
        
        // give JavaScript time to trigger additional ajax requests after onload
        $session->wait(100); 

        // Wait for an ajax request to complete, but only for a maximum of X seconds to avoid deadlocks
        $session->wait(5000,
            "(typeof window.__ajaxStatus !== 'undefined' ? window.__ajaxStatus() : 'no ajax') !== 'waiting'"
        );

        // The 'sleep' config setting will be respected in the comparator logic,
        // and is required to be at least ~200ms to give the browser a chance to finish rendering

        $diff = $this->screenshotComparator->takeScreenshot($this->context, $step);
        if ($diff > 0 && $this->failOnDiff) {
            // There were differences between the two screenshots
            throw new PerceptualDiffException(sprintf('There was a perceptual difference of %d', $diff));
        }
    }
}