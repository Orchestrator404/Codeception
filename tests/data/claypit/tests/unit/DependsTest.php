<?php

class DependsTest extends \Codeception\Test\Unit
{
    /**
     * @group depends
     * @depends testOne
     */
    public function testTwo($res)
    {
        $this->assertTrue(true);
        $this->assertSame(1, $res);
    }

    /**
     * @group depends
     * @depends testFour
     */
    public function testThree()
    {
        $this->assertTrue(true);
    }

    public function testFour()
    {
        $this->assertTrue(true);
    }


    /**
     * @group depends
     */
    public function testOne(): int
    {
        $this->assertTrue(false);
        return 1;
    }
}
