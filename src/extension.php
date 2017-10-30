<?php

declare(strict_types=1);

namespace atoum\teamcity;

use mageekguy\atoum;
use mageekguy\atoum\observable;
use mageekguy\atoum\writers;
use mageekguy\atoum\runner;
use mageekguy\atoum\test;

class extension implements atoum\extension
{
    protected $report = null;

    public function __construct(atoum\configurator $configurator = null)
    {
        if ($configurator) {
            $parser = $configurator->getScript()->getArgumentsParser();

            $handler = function ($script, $argument, $values) {
                $script->getRunner()->addTestsFromDirectory(dirname(__DIR__) . '/test/');
            };

            $parser
                ->addHandler($handler, ['--test-ext'])
                ->addHandler($handler, ['--test-it']);
        }

        $this->report = new report();
    }

    public function addToRunner(runner $runner)
    {
        $report = $this->getReport();
        $report->addWriter(new writers\std\out());

        $runner->addExtension($this);
        $runner->addReport($report);

        return $this;
    }

    /**
     * See https://confluence.jetbrains.com/display/TCD65/Predefined+Build+Parameters.
     */
    public function addToRunnerWithinTeamCityEnvironment(runner $runner) {
        if (true === $this->isTeamCityEnvironment()) {
            $this->addToRunner($runner);
        }
    }

    public function isTeamCityEnvironment(): bool {
        $server = $_SERVER ?? [];

        return
            array_key_exists('TEAMCITY_VERSION', $server) ||
            array_key_exists('TEAMCITY_PROJECT_NAME', $server) ||
            array_key_exists('TEAMCITY_BUILDCONF_NAME', $server);
    }

    public function setRunner(runner $runner)
    {
        return $this;
    }

    public function setTest(test $test)
    {
        return $this;
    }

    public function handleEvent($event, observable $observable)
    {
    }

    public function getReport()
    {
        return $this->report;
    }
}
