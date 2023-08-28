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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use WerkraumMedia\ThueCat\Domain\Import\Importer;
use WerkraumMedia\ThueCat\Domain\Repository\Backend\ImportConfigurationRepository;

class ImportConfigurationCommand extends Command
{
    /**
     * @var ImportConfigurationRepository
     */
    private $importConfigurationRepository;

    /**
     * @var Importer
     */
    private $importer;

    public function __construct(
        ImportConfigurationRepository $importConfigurationRepository,
        Importer $importer
    ) {
        parent::__construct();

        $this->importConfigurationRepository = $importConfigurationRepository;
        $this->importer = $importer;
    }

    protected function configure(): void
    {
        $this->setDescription('Executes a single import based on the given configuration.');

        $this->addArgument(
            'configuration',
            InputArgument::REQUIRED,
            'The UID of the import configuration to use'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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

        $importLog = $this->importer->importConfiguration($configuration);

        if ($importLog->hasErrors()) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
