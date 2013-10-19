<?php

namespace Spika;

class PsrTest extends \PHPUnit_Framework_TestCase
{
    protected static $PHP_CS;
    protected static $TARGET_DIR;
    protected static $RESULT_SUCCESS_RE = '/^Time: [\\d\.]+ [^,]+, Memory: [^\\n]+\\n$/';

    public function setUp()
    {
        parent::setUp();

        self::$PHP_CS     = SPIKA_ROOT . '/vendor/bin/phpcs';
        self::$TARGET_DIR = SPIKA_ROOT . '/tests';
    }

    public function testPsr1()
    {
        $cmd = self::$PHP_CS . ' --standard=PSR1 ' . self::$TARGET_DIR;
        $this->assertResult(`$cmd`);
    }

    public function testPsr2()
    {
        $cmd = self::$PHP_CS . ' --standard=PSR2 ' . self::$TARGET_DIR;
        $this->assertResult(`$cmd`);
    }

    protected function assertResult($result)
    {
        assertThat($result, logicalOr(matchesRegularExpression(self::$RESULT_SUCCESS_RE), isNull()));
    }
}
