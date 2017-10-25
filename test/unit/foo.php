<?php

namespace atoum\teamcity\test\unit;

use mageekguy\atoum\test;
use atoum\teamcity\foo as SUT;

class foo extends test
{
    public function test_bla()
    {
        $this->integer(42)->isEqualTo(42);
    }

    public function test_blabla()
    {
        $this->integer(42)->isEqualTo(43);
    }
}
