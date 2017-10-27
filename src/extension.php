<?php

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
