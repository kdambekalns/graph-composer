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

    /**
     * @var array<string, mixed>
     */
    private array $layoutEdge = [
        'fontcolor' => '#767676',
        'fontsize' => 10,
        'color' => '#1A2833'
    ];

    /**
     * @var array<string, mixed>
     */
    private array $layoutEdgeDev = [
        'style' => 'dashed'
    ];

    private bool $showDevDependencies = true;

    private bool $showPhpExtensions = false;

    /** @var string[] */
    private array $ignoredDepVendors = [];

    /** @var string[] */
    private array $ignoredDepPackages = [];

    private DependencyGraph $dependencyGraph;

    private GraphViz $graphviz;

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

    public function createGraph(): Graph
    {
        $graph = new Graph();

        foreach ($this->dependencyGraph->getPackages() as $package) {
            $name = $package->getName();
            $start = $graph->createVertex($name, true);

            $label = $name;
            if ($package->getVersion() !== '') {
                $label .= ': ' . $package->getVersion();
            }

            $this->setLayout($start, ['label' => $label] + self::LAYOUT_VERTEX);

            $vendor = strstr($name, '/', true);
            if (in_array($name, $this->ignoredDepPackages, true)
                || ($vendor !== false && in_array($vendor, $this->ignoredDepVendors, true))) {
                continue;
            }

            foreach ($package->getOutEdges() as $requires) {
                if (!$this->showDevDependencies && $requires->isDevDependency()) {
                    continue;
                }
                if (!$this->showPhpExtensions && $requires->getDestPackage()->isPhpExtension()) {
                    continue;
                }

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

        if (!$this->showDevDependencies || $this->ignoredDepVendors !== [] || $this->ignoredDepPackages !== []) {
            $rootVertex = $graph->getVertex($this->dependencyGraph->getRootPackage()->getName());
            $reachable = [$rootVertex->getId() => true];
            $queue = [$rootVertex];
            while (!empty($queue)) {
                $vertex = array_shift($queue);
                foreach ($vertex->getEdgesOut() as $edge) {
                    $target = $edge->getVertexEnd();
                    if (!isset($reachable[$target->getId()])) {
                        $reachable[$target->getId()] = true;
                        $queue[] = $target;
                    }
                }
            }
            foreach ($graph->getVertices() as $vertex) {
                if (!isset($reachable[$vertex->getId()])) {
                    $vertex->destroy();
                }
            }
        }

        $root = $graph->getVertex($this->dependencyGraph->getRootPackage()->getName());
        $this->setLayout($root, self::LAYOUT_VERTEX_ROOT);

        return $graph;
    }

    /**
     * @param array<string, mixed> $layout
     */
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

    /**
     * @param string[] $vendors
     */
    public function setIgnoredDepVendors(array $vendors): static
    {
        $this->ignoredDepVendors = $vendors;
        return $this;
    }

    /**
     * @param string[] $packages
     */
    public function setIgnoredDepPackages(array $packages): static
    {
        $this->ignoredDepPackages = $packages;
        return $this;
    }

    public function setShowPhpExtensions(bool $show): static
    {
        $this->showPhpExtensions = $show;
        return $this;
    }

    public function setShowDevDependencies(bool $show): static
    {
        $this->showDevDependencies = $show;
        return $this;
    }

    public function setFormat(string $format): static
    {
        $this->graphviz->setFormat($format);

        return $this;
    }
}
