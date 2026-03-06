<?php
declare(strict_types=1);

namespace Clue\GraphComposer\Graph;

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Attribute\AttributeAware;
use Fhaculty\Graph\Attribute\AttributeBagNamespaced;
use Graphp\GraphViz\GraphViz;
use JMS\Composer\DependencyAnalyzer;
use JMS\Composer\Graph\DependencyGraph;

class GraphComposer
{
    private const LAYOUT_VERTEX = [
        'fillcolor' => '#eeeeee',
        'style' => 'filled, rounded',
        'shape' => 'box',
        'fontcolor' => '#314B5F'
    ];

    private const LAYOUT_VERTEX_ROOT = [
        'style' => 'filled, rounded, bold'
    ];

    private array $layoutEdge = [
        'fontcolor' => '#767676',
        'fontsize' => 10,
        'color' => '#1A2833'
    ];

    private array $layoutEdgeDev = [
        'style' => 'dashed'
    ];

    private DependencyGraph $dependencyGraph;

    /**
     * @var ?GraphViz
     */
    private ?GraphViz $graphviz;

    /**
     *
     * @param string $dir
     * @param GraphViz|null $graphviz
     */
    public function __construct(string $dir, ?GraphViz $graphviz = null)
    {
        if ($graphviz === null) {
            $graphviz = new GraphViz();
            $graphviz->setFormat('svg');
        }
        $analyzer = new DependencyAnalyzer();
        $this->dependencyGraph = $analyzer->analyze($dir);
        $this->graphviz = $graphviz;
    }

    /**
     *
     * @return Graph
     */
    public function createGraph(): Graph
    {
        $graph = new Graph();

        foreach ($this->dependencyGraph->getPackages() as $package) {
            $name = $package->getName();
            $start = $graph->createVertex($name, true);

            $label = $name;
            if ($package->getVersion() !== null) {
                $label .= ': ' . $package->getVersion();
            }

            $this->setLayout($start, ['label' => $label] + self::LAYOUT_VERTEX);

            foreach ($package->getOutEdges() as $requires) {
                $targetName = $requires->getDestPackage()->getName();
                $target = $graph->createVertex($targetName, true);

                $label = $requires->getVersionConstraint();

                $edge = $start->createEdgeTo($target);
                $this->setLayout($edge, ['label' => $label] + $this->layoutEdge);

                if ($requires->isDevDependency()) {
                    $this->setLayout($edge, $this->layoutEdgeDev);
                }
            }
        }

        $root = $graph->getVertex($this->dependencyGraph->getRootPackage()->getName());
        $this->setLayout($root, self::LAYOUT_VERTEX_ROOT);

        return $graph;
    }

    private function setLayout(AttributeAware $entity, array $layout): void
    {
        $bag = new AttributeBagNamespaced($entity->getAttributeBag(), 'graphviz.');
        $bag->setAttributes($layout);
    }

    public function displayGraph(): void
    {
        $graph = $this->createGraph();

        $this->graphviz->display($graph);
    }

    public function getImagePath(): string
    {
        $graph = $this->createGraph();

        return $this->graphviz->createImageFile($graph);
    }

    public function setFormat($format): static
    {
        $this->graphviz->setFormat($format);

        return $this;
    }
}
