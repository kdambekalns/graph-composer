<?php
declare(strict_types=1);

namespace Clue\GraphComposer\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Clue\GraphComposer\Graph\GraphComposer;

class Show extends Command
{
    protected function configure(): void
    {
        $this->setName('show')
             ->setDescription('Show dependency graph image for given project directory')
             ->addArgument('dir', InputArgument::OPTIONAL, 'Path to project directory to scan', '.')
             ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Image format (svg, png, jpeg)', 'svg')
             ->addOption('dev', null, InputOption::VALUE_NONE, 'If set, require-dev dependencies are included')
             ->addOption('php-exts', null, InputOption::VALUE_NONE, 'If set, PHP extension dependencies are included');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $graph = new GraphComposer((string)$input->getArgument('dir'));
        $graph->setFormat((string)$input->getOption('format'));
        $graph->setShowDevDependencies((bool)$input->getOption('dev'));
        $graph->setShowPhpExtensions((bool)$input->getOption('php-exts'));
        $graph->displayGraph();

        return 0;
    }
}
