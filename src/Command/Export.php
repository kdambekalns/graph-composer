<?php
declare(strict_types=1);

namespace Clue\GraphComposer\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Clue\GraphComposer\Graph\GraphComposer;

class Export extends Command
{
    protected function configure(): void
    {
        $this->setName('export')
             ->setDescription('Export dependency graph image for given project directory')
             ->addArgument('dir', InputArgument::OPTIONAL, 'Path to project directory to scan', '.')
             ->addArgument('output', InputArgument::OPTIONAL, 'Path to output image file')

             // add output format option. default value MUST NOT be given, because default is to overwrite with output extension
             ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Image format (svg, png, jpeg)'/*, 'svg'*/)

             ->addOption('dev', null, InputOption::VALUE_NONE, 'If set, require-dev dependencies are included')
             ->addOption('php-exts', null, InputOption::VALUE_NONE, 'If set, PHP extension dependencies are included')
             ->addOption('ignore-deps-vendor', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Ignore outgoing dependencies of packages with given vendor', [])
             ->addOption('ignore-deps-package', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Ignore outgoing dependencies of given package', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $graph = new GraphComposer((string)$input->getArgument('dir'));
        $graph->setShowDevDependencies((bool)$input->getOption('dev'));
        $graph->setShowPhpExtensions((bool)$input->getOption('php-exts'));
        $graph->setIgnoredDepVendors($input->getOption('ignore-deps-vendor'));
        $graph->setIgnoredDepPackages($input->getOption('ignore-deps-package'));

        $target = (string)$input->getArgument('output');
        if ($target !== '') {
            if (is_dir($target)) {
                $target = rtrim($target, '/') . '/graph-composer.svg';
            }

            $filename = basename($target);
            $pos = strrpos($filename, '.');
            if ($pos !== false && isset($filename[$pos + 1])) {
                // extension found and not empty
                $graph->setFormat(substr($filename, $pos + 1));
            }
        }

        $format = (string)$input->getOption('format');
        if ($format !== '') {
            $graph->setFormat($format);
        }

        $path = $graph->getImagePath();

        if ($target !== '') {
            rename($path, $target);
        } else {
            readfile($path);
        }

        return 0;
    }
}
