<?php

declare(strict_types=1);

namespace atoum\teamcity\test\unit;

use DateTime;
use mageekguy\atoum\runner;
use mageekguy\atoum\score;
use mageekguy\atoum\test;
use mock\atoum\teamcity\report as SUT;

class report extends test
{
    const TEST_SUITE_NAME = 'C';
    const TEST_CASE_NAME  = 'f';
    const TEST_PATH       = '/dev/null';
    const TEST_DURATION   = 0.42;

    public function testTestRunStart()
    {
        $this
            ->getMockedWriterForEvents(
                function ($report, $test, $runner) {
                    $report->handleEvent(runner::runStart, $runner);
                    $report->handleEvent(test::runStart, $test);
                    $report->handleEvent(runner::runStop, $runner);
                },
                $timestamp
            )
            ->call('write')
            ->withArguments(
                "##teamcity[testSuiteStarted name='" . static::TEST_SUITE_NAME . "' timestamp='$timestamp']\n"
            )
            ->once();
    }

    public function testTestAfterTestMethod()
    {
        $this
            ->getMockedWriterForEvents(
                function ($report, $test, $runner) {
                    $report->handleEvent(runner::runStart, $runner);
                    $report->handleEvent(test::afterTestMethod, $test);
                    $report->handleEvent(runner::runStop, $runner);
                },
                $timestamp
            )
            ->call('write')
            ->withArguments(
                "##teamcity[" .
                    "testStarted " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "locationHint='php_qn://" . static::TEST_PATH . "::\\" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "timestamp='$timestamp'" .
                "]\n"
            )
            ->once();
    }

    public function testTestSuccess()
    {
        $this
            ->getMockedWriterForEvents(
                function ($report, $test, $runner) {
                    $report->handleEvent(runner::runStart, $runner);
                    $report->handleEvent(test::success, $test);
                    $report->handleEvent(runner::runStop, $runner);
                },
                $timestamp
            )
            ->call('write')
            ->withArguments(
                "##teamcity[" .
                    "testFinished " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "duration='" . static::TEST_DURATION . "' " .
                    "timestamp='$timestamp'" .
                "]\n"
            )
            ->once();
    }

    public function testTestFail()
    {
        $this
            ->getMockedWriterForEvents(
                function ($report, $test, $runner) {
                    $this->calling($test->getScore())->getFailAssertions = [
                        [
                            'class'  => static::TEST_SUITE_NAME,
                            'method' => static::TEST_CASE_NAME,
                            'fail'   => 'foo' . "\n" . 'bar',
                            'file'   => '/dev/null',
                            'line'   => 42,
                        ]
                    ];

                    $report->handleEvent(runner::runStart, $runner);
                    $report->handleEvent(test::fail, $test);
                    $report->handleEvent(runner::runStop, $runner);
                },
                $timestamp
            )
            ->call('write')
            ->withArguments(
                "##teamcity[" .
                    "testFailed " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "message='foo' " .
                    "details='In /dev/null at line 42: foo|nbar' " .
                    "timestamp='$timestamp'" .
                "]\n" .

                "##teamcity[" .
                    "testFinished " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "duration='" . static::TEST_DURATION . "' " .
                    "timestamp='$timestamp'" .
                "]\n"
            )
            ->once();
    }

    public function testTestVoid()
    {
        $this
            ->getMockedWriterForEvents(
                function ($report, $test, $runner) {
                    $report->handleEvent(runner::runStart, $runner);
                    $report->handleEvent(test::void, $test);
                    $report->handleEvent(runner::runStop, $runner);
                },
                $timestamp
            )
            ->call('write')
            ->withArguments(
                "##teamcity[" .
                    "testIgnored " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "message='void' " .
                    "timestamp='$timestamp'" .
                "]\n" .

                "##teamcity[" .
                    "testFinished " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "duration='" . static::TEST_DURATION . "' " .
                    "timestamp='$timestamp'" .
                "]\n"
            )
            ->once();
    }

    public function testTestUncompleted()
    {
        $this
            ->getMockedWriterForEvents(
                function ($report, $test, $runner) {
                    $this->calling($test->getScore())->getUncompletedMethods = [
                        [
                            'class'    => static::TEST_SUITE_NAME,
                            'method'   => static::TEST_CASE_NAME,
                            'exitCode' => 7,
                            'output'   => 'foobar'
                        ]
                    ];

                    $report->handleEvent(runner::runStart, $runner);
                    $report->handleEvent(test::uncompleted, $test);
                    $report->handleEvent(runner::runStop, $runner);
                },
                $timestamp
            )
            ->call('write')
            ->withArguments(
                "##teamcity[" .
                    "testIgnored " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "message='uncompleted' " .
                    "details='Exit code: 7|nOutput: foobar' " .
                    "timestamp='$timestamp'" .
                "]\n" .

                "##teamcity[" .
                    "testFinished " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "duration='" . static::TEST_DURATION . "' " .
                    "timestamp='$timestamp'" .
                "]\n"
            )
            ->once();
    }

    public function testTestSkipped()
    {
        $this
            ->getMockedWriterForEvents(
                function ($report, $test, $runner) {
                    $this->calling($test->getScore())->getSkippedMethods = [
                        [
                            'class'   => static::TEST_SUITE_NAME,
                            'method'  => static::TEST_CASE_NAME,
                            'message' => 'foobar'
                        ]
                    ];

                    $report->handleEvent(runner::runStart, $runner);
                    $report->handleEvent(test::skipped, $test);
                    $report->handleEvent(runner::runStop, $runner);
                },
                $timestamp
            )
            ->call('write')
            ->withArguments(
                "##teamcity[" .
                    "testIgnored " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "message='skipped' " .
                    "details='foobar' " .
                    "timestamp='$timestamp'" .
                "]\n" .

                "##teamcity[" .
                    "testFinished " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "duration='0' " .
                    "timestamp='$timestamp'" .
                "]\n"
            )
            ->once();
    }

    public function testTestError()
    {
        $this
            ->getMockedWriterForEvents(
                function ($report, $test, $runner) {
                    $this->calling($test->getScore())->getErrors = [
                        [
                            'class'     => static::TEST_SUITE_NAME,
                            'method'    => static::TEST_CASE_NAME,
                            'type'      => 123,
                            'errorFile' => '/dev/null',
                            'errorLine' => 7,
                            'message'   => 'foobar'
                        ]
                    ];

                    $report->handleEvent(runner::runStart, $runner);
                    $report->handleEvent(test::error, $test);
                    $report->handleEvent(runner::runStop, $runner);
                },
                $timestamp
            )
            ->call('write')
            ->withArguments(
                "##teamcity[" .
                    "testFailed " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "message='PHP error number 123' " .
                    "details='Error raised in /dev/null at line 7:|nfoobar' " .
                    "timestamp='$timestamp'" .
                "]\n" .

                "##teamcity[" .
                    "testFinished " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "duration='" . static::TEST_DURATION . "' " .
                    "timestamp='$timestamp'" .
                "]\n"
            )
            ->once();
    }

    public function testTestException()
    {
        $this
            ->getMockedWriterForEvents(
                function ($report, $test, $runner) {
                    $this->calling($test->getScore())->getExceptions = [
                        [
                            'class'     => static::TEST_SUITE_NAME,
                            'method'    => static::TEST_CASE_NAME,
                            'value'     => 'foobar'
                        ]
                    ];

                    $report->handleEvent(runner::runStart, $runner);
                    $report->handleEvent(test::exception, $test);
                    $report->handleEvent(runner::runStop, $runner);
                },
                $timestamp
            )
            ->call('write')
            ->withArguments(
                "##teamcity[" .
                    "testFailed " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "message='exception' " .
                    "details='foobar' " .
                    "timestamp='$timestamp'" .
                "]\n" .

                "##teamcity[" .
                    "testFinished " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "duration='" . static::TEST_DURATION . "' " .
                    "timestamp='$timestamp'" .
                "]\n"
            )
            ->once();
    }

    public function testTestRuntimeException()
    {
        $this
            ->getMockedWriterForEvents(
                function ($report, $test, $runner) {
                    $report->handleEvent(runner::runStart, $runner);
                    $report->handleEvent(test::runtimeException, $test);
                    $report->handleEvent(runner::runStop, $runner);
                },
                $timestamp
            )
            ->call('write')
            ->withArguments(
                "##teamcity[" .
                    "testFailed " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "message='A runtime exception has been thrown' " .
                    "timestamp='$timestamp'" .
                "]\n" .

                "##teamcity[" .
                    "testFinished " .
                    "name='" . static::TEST_SUITE_NAME . "::" . static::TEST_CASE_NAME . "' " .
                    "duration='" . static::TEST_DURATION . "' " .
                    "timestamp='$timestamp'" .
                "]\n"
            )
            ->once();
    }

    public function testRunStop()
    {
        $this
            ->getMockedWriterForEvents(
                function ($report, $test, $runner) {
                    $report->handleEvent(runner::runStart, $runner);
                    $report->handleEvent(test::runStop, $test);
                    $report->handleEvent(runner::runStop, $runner);
                },
                $timestamp
            )
            ->call('write')
            ->withArguments(
                "##teamcity[" .
                    "testSuiteFinished " .
                    "name='" . static::TEST_SUITE_NAME . "' " .
                    "timestamp='$timestamp'" .
                "]\n"
            )
            ->once();
    }

    /**
     * @dataProvider escapings
     */
    public function testEscapeValue(string $input, string $output)
    {
        $this
            ->given($report = new SUT())
            ->when($result = $report->escapeValue($input))
            ->then
                ->string($result)
                    ->isIdenticalTo($output);
    }

    protected function escapings(): array
    {
        return [
            ['a|b', 'a||b'],
            ['a' . "\n" . 'b', 'a|nb'],
            ['a' . "\r" . 'b', 'a|rb'],
            ['a[b', 'a|[b'],
            ['a]b', 'a|]b'],
            ['a' . "\u{85}" . 'b', 'a|xb'],
            ['a' . "\u{2028}" . 'b', 'a|lb'],
            ['a' . "\u{2029}" . 'b', 'a|pb'],
            ['a\'b', 'a|\'b'],
        ];
    }

    protected function getMockedWriterForEvents(callable $eventScheduler, &$timestamp) {
        return
            $this
                ->given(
                    $dateTime  = new DateTime(),
                    $timestamp = $this->getTimestamp($dateTime),

                    $report = $this->newReport($dateTime),
                    $writer = $this->newWriter($report),

                    $runner = $this->newRunnerObservable(),
                    $test   = $this->newTestObservable()
                )
                ->when($eventScheduler($report, $test, $runner))
                ->then
                    ->mock($writer);
    }

    protected function getTimestamp(Datetime $dateTime): string
    {
        return $dateTime->format('Y-m-d\TH:i:s.vP');
    }

    protected function newReport(DateTime $dateTime)
    {
        $report = new SUT();
        $this->calling($report)->newDateTime = $dateTime;

        return $report;
    }

    protected function newWriter(SUT $report)
    {
        $writer = new \mock\mageekguy\atoum\writers\file('/dev/null');
        $report->addWriter($writer);

        return $writer;
    }

    protected function newRunnerObservable()
    {
        $this->mockGenerator->orphanize('__construct');
        $observable = new \mock\mageekguy\atoum\runner();

        return $observable;
    }

    protected function newTestObservable()
    {
        $this->mockGenerator->orphanize('__construct');
        $observable = new \mock\mageekguy\atoum\test();
        $this->calling($observable)->getClass = static::TEST_SUITE_NAME;
        $this->calling($observable)->getCurrentMethod = static::TEST_CASE_NAME;
        $this->calling($observable)->getPath = static::TEST_PATH;
        $this->calling($observable)->getScore = $this->newScore();

        return $observable;
    }

    protected function newScore(): score
    {
        $this->mockGenerator->orphanize('__construct');
        $score = new \mock\mageekguy\atoum\score();
        $this->calling($score)->getDurations = [
            [
                'class'  => static::TEST_SUITE_NAME,
                'method' => static::TEST_CASE_NAME,
                'value'  => static::TEST_DURATION / 1000
            ],
            [
                'class'  => 'X',
                'method' => 'y',
                'value'  => static::TEST_DURATION / 1000
            ]
        ];

        return $score;
    }
}
