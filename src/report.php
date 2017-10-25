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
                    '##teamcity[testStarted name=\'%s\' captureStandardOutput=\'true\']',
                    $observable->getClass() . '::' . $observable->getCurrentMethod()
                );

                break;

            /**
             * Test verdict.
             */

            case test::success:
                $this->add(
                    '##teamcity[testFinished name=\'%s\' duration=\'%s\']',
                    $observable->getClass() . '::' . $observable->getCurrentMethod(),
                    ''
                );

                break;

            case test::fail:
            case test::error:
                $this->add(
                    '##teamcity[testFailed name=\'%s\' message=\'%s\' captureStandardOutput=\'true\' details=\'%s\']',
                    $observable->getClass() . '::' . $observable->getCurrentMethod(),
                    '',
                    ''
                );

                break;

                $this->add($event);

                break;

            case test::void:
                $this->add(
                    '##teamcity[testIgnored name=\'%s\' message=\'%s\']',
                    $observable->getClass() . '::' . $observable->getCurrentMethod(),
                    'void'
                );

                break;

            case test::uncompleted:
                $this->add(
                    '##teamcity[testIgnored name=\'%s\' message=\'%s\']',
                    $observable->getClass() . '::' . $observable->getCurrentMethod(),
                    'uncompleted'
                );

                break;

            case test::skipped:
                $this->add(
                    '##teamcity[testIgnored name=\'%s\' message=\'%s\']',
                    $observable->getClass() . '::' . $observable->getCurrentMethod(),
                    'skipped'
                );

                break;

            case test::exception:
                $this->add(
                    '##teamcity[testFailed name=\'%s\' message=\'%s\' captureStandardOutput=\'true\' details=\'%s\']',
                    $observable->getClass() . '::' . $observable->getCurrentMethod(),
                    '',
                    ''
                );

                break;

            case test::runtimeException:
                $this->add(
                    '##teamcity[testFailed name=\'%s\' message=\'%s\' captureStandardOutput=\'true\' details=\'%s\']',
                    $observable->getClass() . '::' . $observable->getCurrentMethod(),
                    '',
                    ''
                );

                break;

            case test::fail:
                $this->add(
                    '##teamcity[testFailed name=\'%s\' message=\'%s\' captureStandardOutput=\'true\' details=\'%s\']',
                    $observable->getClass() . '::' . $observable->getCurrentMethod(),
                    '',
                    ''
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

    protected function add(string $format, string ...$messages)
    {
        $this->string .= vsprintf(
            $format,
            array_map(
                function ($message) {
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
                        $message
                    );

                    return $message;
                },
                $messages
            )
        ) . "\n";
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
