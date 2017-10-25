<?php

declare(strict_types=1);

namespace atoum\teamcity;

use mageekguy\atoum\observable;
use mageekguy\atoum\reports\asynchronous;
use mageekguy\atoum\runner;
use mageekguy\atoum\test;

class report extends asynchronous
{
    public function handleEvent($event, observable $observable)
    {
        // By runtime order.
        switch ($event) {
            /**
             * Runner starts.
             */

            case runner::runStart:
                // noop

                break;

            /**
             * Test case starts.
             */

            case test::runStart:
                // noop

                break;

            case test::beforeSetUp:
                // noop

                break;

            case test::afterSetUp:
                // noop

                break;

            case test::beforeTestMethod:
                // noop

                break;

            case test::afterTestMethod:
                $this->add(
                    'testStarted',
                    [
                        'name'                  => $observable->getClass() . '::' . $observable->getCurrentMethod(),
                        'captureStandardOutput' => 'true'
                    ]
                );

                break;

            /**
             * Test verdict.
             */

            case test::success:
                $testSuiteName = $observable->getClass();
                $testCaseName  = $observable->getCurrentMethod();
                $testDuration  = '';

                foreach ($observable->getScore()->getDurations() as $duration) {
                    if ($testSuiteName === $duration['class'] &&
                        $testCaseName  === $duration['method']) {
                        $testDuration = (string) $duration['value'];

                        break;
                    }
                }

                $this->add(
                    'testFinished',
                    [
                        'name'     => $testSuiteName . '::' . $testCaseName,
                        'duration' => $testDuration
                    ]
                );

                break;

            case test::fail:
                $testSuiteName = $observable->getClass();
                $testCaseName  = $observable->getCurrentMethod();
                $message       = '';
                $details       = '';

                foreach ($observable->getScore()->getFailAssertions() as $failAssertion) {
                    if ($testSuiteName === $failAssertion['class'] &&
                        $testCaseName  === $failAssertion['method']) {
                        if (false !== $newline = strpos($failAssertion['fail'], "\n")) {
                            $message = substr($failAssertion['fail'], 0, $newline);
                        } else {
                            $message = $failAssertion['fail'];
                        }

                        $details = sprintf(
                            'In %s at line %d: %s',
                            $failAssertion['file'],
                            $failAssertion['line'],
                            $failAssertion['fail']
                        );

                        break;
                    }
                }

                $this->add(
                    'testFailed',
                    [
                        'name'                  => $testSuiteName . '::' . $testCaseName,
                        'message'               => $message,
                        'captureStandardOutput' => 'true',
                        'details'               => $details
                    ]
                );

                break;

            case test::void:
                $this->add(
                    'testIgnored',
                    [
                        'name'    => $observable->getClass() . '::' . $observable->getCurrentMethod(),
                        'message' => 'void'
                    ]
                );

                break;

            case test::uncompleted:
                $this->add(
                    'testIgnored',
                    [
                        'name'    => $observable->getClass() . '::' . $observable->getCurrentMethod(),
                        'message' => 'uncompleted'
                    ]
                );

                break;

            case test::skipped:
                $testSuiteName = $observable->getClass();
                $testCaseName  = $observable->getCurrentMethod();
                $details       = '';

                foreach ($observable->getScore()->getSkippedMethods() as $skippedMethod) {
                    if ($testSuiteName === $skippedMethod['class'] &&
                        $testCaseName  === $skippedMethod['method']) {
                        $details = $skippedMethod['message'];

                        break;
                    }
                }

                $this->add(
                    'testIgnored',
                    [
                        'name'    => $testSuiteName . '::' . $testCaseName,
                        'message' => 'skipped',
                        'details' => $details
                    ]
                );

                break;

            case test::error:
                $this->add(
                    'testFailed',
                    [
                        'name'                  => $observable->getClass() . '::' . $observable->getCurrentMethod(),
                        'message'               => '',
                        'captureStandardOutput' => 'true',
                        'details'               => ''
                    ]
                );

                break;

            case test::exception:
                $this->add(
                    'testFailed',
                    [
                        'name'                  => $observable->getClass() . '::' . $observable->getCurrentMethod(),
                        'message'               => '',
                        'captureStandardOutput' => 'true',
                        'details'               => ''
                    ]
                );

                break;

            case test::runtimeException:
                $this->add(
                    'testFailed',
                    [
                        'name'                  => $observable->getClass() . '::' . $observable->getCurrentMethod(),
                        'message'               => '',
                        'captureStandardOutput' => 'true',
                        'details'               => ''
                    ]
                );

                break;

            /**
             * Test case stops.
             */

            case test::beforeTearDown:
                // noop

                break;

            case test::afterTearDown:
                // noop

                break;

            case test::runStop:
                $this->flush();

                break;

            /**
             * Runner stops.
             */

            case runner::runStop:
                $this->flush();

                break;
        }
    }

    protected function add(string $eventName, array $arguments)
    {
        $this->string .= '##teamcity[' . $eventName;

        foreach ($arguments as $name => $value) {
            $this->string .= ' ' . $name . '=\'' . $this->escapeValue($value) . '\'';
        }

        $this->string .= ']' . "\n";
    }

    public function escapeValue(string $value): string
    {
        return preg_replace(
            [
                '/\x1B.*?m/',
                '/\|/',
                '/\n/',
                '/\r/',
                '/\[/',
                '/\]/',
                '/\x0085/',
                '/\x2028/',
                '/\x2029/',
                '/\'/',
            ],
            [
                '',
                '||',
                '|n',
                '|r',
                '|[',
                '|]',
                '|x',
                '|l',
                '|p',
                '|\'',
            ],
            $value
        );
    }

    protected function flush()
    {
        if (null === $this->string) {
            return;
        }

        foreach ($this->getWriters() as $writer) {
            $writer->writeAsynchronousReport($this);
        }

        $this->string = null;
    }
}
