<?php
/**
 * This file is part of the backcups project.
 *
 * Copyright (c)
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Desarrolla2\Backups\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Process\Process;
use RuntimeException;

/**
 * Class BackupsCommand
 *
 * @author Daniel González <daniel.gonzalez@freelancemadrid.es>
 */

class BackupsCommand extends Command
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $configFile;

    /**
     * @var string
     */
    protected $target;

    /**
     * @var array
     */
    protected $config;

    /**
     * configure
     */
    protected function configure()
    {
        $this->setName('backups:execute');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->config['items'] as $items) {
            $this->backup($items);
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->parser = new Parser();
        $this->configFile = realpath(__DIR__ . '/../../../../app/config/config.yml');
        $this->config = $this->getConfiguration();
        $this->createTarget();
    }

    /**
     * @throws RuntimeException
     */
    protected function createTarget()
    {
        $now = new \DateTime();
        $this->target = $this->config['backups_directory'] . '/' . $now->format('Ymd-His') . '/';
        if (!mkdir($this->target, 0777, true)) {
            throw RuntimeException('Could not create the destination directory ' . $this->target);
        }
        $this->output->writeln(
            'Created <comment>' . $this->target . '</comment>'
        );
    }

    /**
     * @param $item
     * @return bool
     * @throws \RuntimeException
     */
    protected function backup($item)
    {
        $cmd = 'tar -cvzf ' . $this->target . $item['name'] . '.tar.gz ' . $item['path'];
        $process = new Process($cmd);
        $process->run();

        if ($process->isSuccessful()) {
            $this->output->writeln(
                '<comment>' . $item['name'] . '</comment> ' . $item['path'] . ' <info> OK</info> '
            );

            return true;
        }
        throw new \RuntimeException($process->getErrorOutput());
    }

    protected function getConfiguration()
    {

        $config = $this->parser->parse(file_get_contents($this->configFile));

        return $config['config'];
    }
}