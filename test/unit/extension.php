<?php

declare(strict_types=1);

namespace atoum\teamcity\test\unit;

use atoum\teamcity\extension as SUT;
use mageekguy\atoum\test;

class extension extends test
{
    public function testIsTeamCityEnvironmentWithVersion() {
        $this->_testIsTeamCityEnvironmentWithVersion('TEAMCITY_VERSION');
    }

    public function testIsTeamCityEnvironmentWithProjectName() {
        $this->_testIsTeamCityEnvironmentWithVersion('TEAMCITY_PROJECT_NAME');
    }

    public function testIsTeamCityEnvironmentWithBuildConfName() {
        $this->_testIsTeamCityEnvironmentWithVersion('TEAMCITY_BUILDCONF_NAME');
    }

    protected function _testIsTeamCityEnvironmentWithVersion(string $variable) {
        unset($_SERVER);

        $this
            ->given(
                $_SERVER[$variable] = 'foo',
                $extension = new SUT()
            )
            ->when($result = $extension->isTeamCityEnvironment())
            ->then
                ->boolean($result)
                    ->isTrue();
    }

    public function testIsTeamCityEnvironmentWithoutAnyVariable() {
        unset($_SERVER);

        $this
            ->given($extension = new SUT())
            ->when($result = $extension->isTeamCityEnvironment())
            ->then
                ->boolean($result)
                    ->isFalse();
    }
}
