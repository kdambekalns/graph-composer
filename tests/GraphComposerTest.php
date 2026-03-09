<?php
declare(strict_types=1);

use Clue\GraphComposer\Graph\GraphComposer;
use Fhaculty\Graph\Graph;
use Graphp\GraphViz\GraphViz;
use PHPUnit\Framework\TestCase;

class GraphVizMockDisplay extends GraphViz
{
    public int $called = 0;
    public function display(Graph $graph): void
    {
        ++$this->called;
    }
}

class GraphVizMockCreateImageFile extends GraphViz
{
    public int $called = 0;
    public function createImageFile(Graph $graph): string
    {
        return 'test' . ++$this->called . '.png';
    }
}

class GraphVizMockSetFormat extends GraphViz
{
    public ?string $called = null;
    public function setFormat($format)
    {
        $this->called = $format;
    }
}

class GraphComposerTest extends TestCase
{
    public function testCreateGraph(): void
    {
        $dir = __DIR__ . '/../';

        $graphComposer = new GraphComposer($dir);
        $graph = $graphComposer->createGraph();

        static::assertNotEmpty($graph->getVertices());
    }

    public function testDisplayGraphCallsDisplayGraphViz(): void
    {
        $dir = __DIR__ . '/../';

        // mocking with PHP 7.4 reports error with legacy PHPUnit, create manual mock classes instead
        $graphviz = new GraphVizMockDisplay();

        $graphComposer = new GraphComposer($dir, $graphviz);
        $graphComposer->displayGraph();

        static::assertEquals(1, $graphviz->called);
    }

    public function testGetImagePathWillCreateTemporaryImageFileViaGraphViz(): void
    {
        $dir = __DIR__ . '/../';

        // mocking with PHP 7.4 reports error with legacy PHPUnit, create manual mock classes instead
        $graphviz = new GraphVizMockCreateImageFile();

        $graphComposer = new GraphComposer($dir, $graphviz);
        $ret = $graphComposer->getImagePath();

        static::assertEquals('test1.png', $ret);
    }

    public function testSetFormatWillSetFormatOnGraphViz(): void
    {
        $dir = __DIR__ . '/../';

        // mocking with PHP 7.4 reports error with legacy PHPUnit, create manual mock classes instead
        $graphviz = new GraphVizMockSetFormat();

        $graphComposer = new GraphComposer($dir, $graphviz);
        $ret = $graphComposer->setFormat('gif');

        static::assertEquals($graphComposer, $ret);
        static::assertEquals('gif', $graphviz->called);
    }
}
