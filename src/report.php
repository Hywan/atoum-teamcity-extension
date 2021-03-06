<?php

declare(strict_types=1);

namespace atoum\teamcity;

use DateTime;
use mageekguy\atoum\observable;
use mageekguy\atoum\reports\asynchronous;
use mageekguy\atoum\runner;
use mageekguy\atoum\score;
use mageekguy\atoum\test;

/**
 * Format specification: https://confluence.jetbrains.com/display/TCD8/Build+Script+Interaction+with+TeamCity.
 */
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
                $this->flush();

                break;

            /**
             * Test suite starts.
             */

            case test::runStart:
                $this->add(
                    'testSuiteStarted',
                    [
                        'name' => $observable->getClass()
                    ]
                );

                break;

            /**
             * Test case starts.
             */

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
                $testSuiteName = $observable->getClass();
                $testCaseName  = $observable->getCurrentMethod();
                $testPath      = $observable->getPath();

                $this->add(
                    'testStarted',
                    [
                        'name'         => $testSuiteName . '::' . $testCaseName,
                        'locationHint' => 'php_qn://' . $testPath . '::\\' . $testSuiteName . '::' . $testCaseName
                    ]
                );

                break;

            /**
             * Test verdict.
             */

            case test::success:
                $testSuiteName = $observable->getClass();
                $testCaseName  = $observable->getCurrentMethod();
                $testDuration  = (string) $this->getDuration($observable->getScore(), $testSuiteName, $testCaseName);

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
                $testDuration  = (string) $this->getDuration($observable->getScore(), $testSuiteName, $testCaseName);
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
                        'name'    => $testSuiteName . '::' . $testCaseName,
                        'message' => $message,
                        'details' => $details
                    ]
                );
                $this->add(
                    'testFinished',
                    [
                        'name'     => $testSuiteName . '::' . $testCaseName,
                        'duration' => $testDuration
                    ]
                );

                break;

            case test::void:
                $testSuiteName = $observable->getClass();
                $testCaseName  = $observable->getCurrentMethod();
                $testDuration  = (string) $this->getDuration($observable->getScore(), $testSuiteName, $testCaseName);

                $this->add(
                    'testIgnored',
                    [
                        'name'    => $testSuiteName . '::' . $testCaseName,
                        'message' => 'void'
                    ]
                );
                $this->add(
                    'testFinished',
                    [
                        'name'     => $testSuiteName . '::' . $testCaseName,
                        'duration' => $testDuration
                    ]
                );

                break;

            case test::uncompleted:
                $testSuiteName = $observable->getClass();
                $testCaseName  = $observable->getCurrentMethod();
                $testDuration  = (string) $this->getDuration($observable->getScore(), $testSuiteName, $testCaseName);
                $details       = '';

                foreach ($observable->getScore()->getUncompletedMethods() as $uncompletedMethod) {
                    if ($testSuiteName === $uncompletedMethod['class'] &&
                        $testCaseName  === $uncompletedMethod['method']) {
                        $details = sprintf(
                            'Exit code: %d' . "\n" .
                            'Output: %s',
                            $uncompletedMethod['exitCode'],
                            $uncompletedMethod['output']
                        );

                        break;
                    }
                }

                $this->add(
                    'testIgnored',
                    [
                        'name'    => $observable->getClass() . '::' . $observable->getCurrentMethod(),
                        'message' => 'uncompleted',
                        'details' => $details
                    ]
                );
                $this->add(
                    'testFinished',
                    [
                        'name'     => $testSuiteName . '::' . $testCaseName,
                        'duration' => $testDuration
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
                $this->add(
                    'testFinished',
                    [
                        'name'     => $testSuiteName . '::' . $testCaseName,
                        'duration' => '0'
                    ]
                );

                break;

            case test::error:
                $testSuiteName = $observable->getClass();
                $testCaseName  = $observable->getCurrentMethod();
                $testDuration  = (string) $this->getDuration($observable->getScore(), $testSuiteName, $testCaseName);
                $message       = '';
                $details       = '';

                foreach ($observable->getScore()->getErrors() as $error) {
                    if ($testSuiteName === $error['class'] &&
                        $testCaseName  === $error['method']) {
                        $message = 'PHP error number ' . $error['type'];
                        $details = sprintf(
                            'Error raised in %s at line %d:' . "\n" . '%s',
                            $error['errorFile'],
                            $error['errorLine'],
                            $error['message']
                        );

                        break;
                    }
                }

                $this->add(
                    'testFailed',
                    [
                        'name'    => $testSuiteName . '::' . $testCaseName,
                        'message' => $message,
                        'details' => $details
                    ]
                );
                $this->add(
                    'testFinished',
                    [
                        'name'     => $testSuiteName . '::' . $testCaseName,
                        'duration' => $testDuration
                    ]
                );

                break;

            case test::exception:
                $testSuiteName = $observable->getClass();
                $testCaseName  = $observable->getCurrentMethod();
                $testDuration  = (string) $this->getDuration($observable->getScore(), $testSuiteName, $testCaseName);
                $message       = '';
                $details       = '';

                foreach ($observable->getScore()->getExceptions() as $exception) {
                    if ($testSuiteName === $exception['class'] &&
                        $testCaseName  === $exception['method']) {
                        $message = 'exception';
                        $details = $exception['value'];

                        break;
                    }
                }

                $this->add(
                    'testFailed',
                    [
                        'name'    => $testSuiteName . '::' . $testCaseName,
                        'message' => $message,
                        'details' => $details
                    ]
                );
                $this->add(
                    'testFinished',
                    [
                        'name'     => $testSuiteName . '::' . $testCaseName,
                        'duration' => $testDuration
                    ]
                );

                break;

            case test::runtimeException:
                $testSuiteName = $observable->getClass();
                $testCaseName  = $observable->getCurrentMethod();
                $testDuration  = (string) $this->getDuration($observable->getScore(), $testSuiteName, $testCaseName);

                $this->add(
                    'testFailed',
                    [
                        'name'    => $testSuiteName . '::' . $testCaseName,
                        'message' => 'A runtime exception has been thrown'
                    ]
                );
                $this->add(
                    'testFinished',
                    [
                        'name'     => $testSuiteName . '::' . $testCaseName,
                        'duration' => $testDuration
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

            /**
             * Test suite stops.
             */

            case test::runStop:
                $this->add(
                    'testSuiteFinished',
                    [
                        'name' => $observable->getClass()
                    ]
                );
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

        if (!isset($arguments['timestamp'])) {
            $arguments['timestamp'] = $this->newDateTime()->format('Y-m-d\TH:i:s.vO');
        }

        foreach ($arguments as $name => $value) {
            $this->string .= ' ' . $name . '=\'' . $this->escapeValue($value) . '\'';
        }

        $this->string .= ']' . "\n";
    }

    public function newDateTime(): DateTime
    {
        return new DateTime();
    }

    public function escapeValue(string $value): string
    {
        return preg_replace(
            [
                '/\|/',
                '/\n/',
                '/\r/',
                '/\[/',
                '/\]/',
                '/\x85/u',
                '/\x{2028}/u',
                '/\x{2029}/u',
                '/\'/',
            ],
            [
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
        if (empty($this->string)) {
            return;
        }

        foreach ($this->getWriters() as $writer) {
            $writer->writeAsynchronousReport($this);
        }

        $this->string = null;
    }

    protected function getDuration(score $score, string $testSuiteName, string $testCaseName): float {
        foreach ($score->getDurations() as $duration) {
            if ($testSuiteName === $duration['class'] &&
                $testCaseName  === $duration['method']) {
                return round($duration['value'] * 1000, 0);
            }
        }

        return 0.0;
    }
}
