<?php

declare(strict_types=1);

/*
 * Copyright (C) 2023 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace WerkraumMedia\ThueCat\Command;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;
use WerkraumMedia\ThueCat\Import\Importer;
use WerkraumMedia\ThueCat\Import\ImportLogger;

#[AsCommand(name: 'thuecat:importviaconfiguration')]
class ImportConfigurationCommand extends Command
{
    public function __construct(
        private readonly ImportConfigurationRepository $importConfigurationRepository,
        private readonly Importer $importer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Executes a single import based on the given configuration.');

        $this->addArgument(
            'configuration',
            InputArgument::REQUIRED,
            'The UID of the import configuration to use'
        );

        $this->addOption(
            'fresh',
            null,
            InputOption::VALUE_NONE,
            'Ignore cached API responses and fetch every resource from the API'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initializeBackendAuthentication();

        $configurationUid = $input->getArgument('configuration');
        if (is_numeric($configurationUid)) {
            $configurationUid = (int)$configurationUid;
        } else {
            throw new Exception('No numeric uid for configuration provided.', 1643267138);
        }

        $configuration = $this->importConfigurationRepository->findOneByUid($configurationUid);
        if ($configuration === null) {
            throw new Exception('No configuration found for uid: ' . $configurationUid, 1693228522);
        }
        // Decoration, not input interactivity: it tracks whether a terminal is
        // attached, which is what makes per-item churn readable rather than a
        // flood in a cron or CI log.
        $severity = $this->importer->importConfiguration(
            $configuration,
            new ConsoleProgressRenderer($output, $output->isDecorated()),
            null,
            $input->getOption('fresh') === true
        );
        $io = new SymfonyStyle($input, $output);

        if ($this->isFailureSeverity($severity)) {
            $io->error(sprintf(
                'Import of configuration %d failed (severity: %s). See the import log for details.',
                $configurationUid,
                $severity
            ));

            return Command::FAILURE;
        }

        if ($severity === ImportLogger::SEVERITY_WARNING) {
            $io->success(sprintf(
                'Import of configuration %d completed with warnings. Records were '
                . 'imported; see the import log for what caused the warnings.',
                $configurationUid
            ));

            return Command::SUCCESS;
        }

        $io->success(sprintf('Import of configuration %d completed.', $configurationUid));

        return Command::SUCCESS;
    }

    /**
     * Map our PSR-3-style severity onto a Command exit code. Anything at or
     * above 'error' fails the command; warnings and below let the operator
     * inspect the log without flagging the run as broken to whatever
     * scheduler invoked the command.
     */
    private function isFailureSeverity(string $severity): bool
    {
        return in_array($severity, [
            ImportLogger::SEVERITY_ERROR,
            ImportLogger::SEVERITY_CRITICAL,
            ImportLogger::SEVERITY_ALERT,
            ImportLogger::SEVERITY_EMERGENCY,
        ], true);
    }
}
