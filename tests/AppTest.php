<?php
declare(strict_types=1);

use Clue\GraphComposer\App;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    public function testVersionReturnsDev(): void
    {
        $app = new App();

        static::assertEquals('@dev', $app->getVersion());
    }
}
