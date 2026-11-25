<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "thuecat".
 *
 * Copyright (C) werkraum-media <https://werkraum-media.de/>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * For the full license text, see the LICENSE file distributed with this
 * extension.
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

namespace WerkraumMedia\ThueCat\Tests\Functional;

use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Log\LogDataTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Sets up a logged-in backend user, the `LANG` global and the site configurations
 * for tests that run backend code.
 */
abstract class AbstractBackendTestCase extends AbstractFunctionalTestCase
{
    use LogDataTrait;

    public const PATHS_TO_LINK = [
        'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Import/Sites/' => 'typo3conf/sites',
    ];

    protected array $pathsToLinkInTestInstance = self::PATHS_TO_LINK;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/BackendUser.php');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->create('en_US');
    }

    /**
     * The form data an editor gets when opening the record.
     *
     * @return array<mixed>
     */
    protected function compileEditForm(string $table, int $uid): array
    {
        // Rendered elements resolve record icons through the request's normalizedParams.
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
        ;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $GLOBALS['TYPO3_REQUEST'],
                'tableName' => $table,
                'vanillaUid' => $uid,
                'command' => 'edit',
            ],
            GeneralUtility::makeInstance(TcaDatabaseRecord::class)
        );
    }

    /**
     * The HTML of one field of the record's edit form, rendered the way the form's field container hands it over.
     */
    protected function renderFormField(string $table, int $uid, string $field): string
    {
        $formData = $this->compileEditForm($table, $uid);
        $processedTca = is_array($formData['processedTca'] ?? null) ? $formData['processedTca'] : [];
        $columns = is_array($processedTca['columns'] ?? null) ? $processedTca['columns'] : [];
        $fieldConf = $columns[$field] ?? null;
        $this->assertIsArray($fieldConf, 'Field ' . $field . ' is not part of the ' . $table . ' form.');
        $config = is_array($fieldConf['config'] ?? null) ? $fieldConf['config'] : [];
        $databaseRow = is_array($formData['databaseRow'] ?? null) ? $formData['databaseRow'] : [];

        $result = GeneralUtility::makeInstance(NodeFactory::class)->create([
            ...$formData,
            'fieldName' => $field,
            'renderType' => $config['renderType'] ?? $config['type'] ?? '',
            'parameterArray' => [
                'fieldConf' => $fieldConf,
                'itemFormElValue' => $databaseRow[$field] ?? '',
                'itemFormElName' => 'data[' . $table . '][' . $uid . '][' . $field . ']',
                'fieldChangeFunc' => [],
            ],
        ])->render();

        return is_string($result['html'] ?? null) ? $result['html'] : '';
    }

    /**
     * @return list<object> in the order they were queued
     */
    protected function queuedMessages(): array
    {
        $rows = $this->getConnectionPool()->getConnectionForTable('sys_messenger_messages')
            ->select(['body'], 'sys_messenger_messages', ['queue_name' => 'default'], [], ['id' => 'ASC'])
            ->fetchAllAssociative()
        ;

        $messages = [];
        foreach ($rows as $row) {
            $body = $row['body'];
            $this->assertIsString($body);
            $messages[] = new PhpSerializer()->decode(['body' => $body])->getMessage();
        }

        return $messages;
    }

    /**
     * Fails the message the way the worker does: stored and being handled when the failure is announced, rejected
     * afterwards.
     */
    protected function failMessage(object $message): void
    {
        $this->get(MessageBusInterface::class)->dispatch($message);
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_messenger_messages');
        $inFlight = $connection->select(['id'], 'sys_messenger_messages', [], [], ['id' => 'DESC'], 1)->fetchOne();
        $connection->update('sys_messenger_messages', ['delivered_at' => date('Y-m-d H:i:s')], ['id' => $inFlight]);

        $this->announceFailure($message);

        $connection->delete('sys_messenger_messages', ['id' => $inFlight]);
    }

    protected function announceFailure(object $message): void
    {
        $envelope = new Envelope($message);
        $this->get(EventDispatcherInterface::class)->dispatch(new WorkerMessageFailedEvent(
            $envelope,
            'doctrine',
            new HandlerFailedException($envelope, [new RuntimeException('Database gone [test]', 1795092847)])
        ));
    }

    /**
     * @return list<array{type: int, error: int, details: string}> the user's error entries, details formatted
     */
    protected function systemLogErrors(int $userId): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_log');
        $rows = $queryBuilder
            ->select('type', 'error', 'details', 'log_data')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq('userid', $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)),
                $queryBuilder->expr()->neq('error', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $errors = [];
        foreach ($rows as $row) {
            $this->assertIsInt($row['type']);
            $this->assertIsInt($row['error']);
            $this->assertIsString($row['details']);
            $errors[] = [
                'type' => $row['type'],
                'error' => $row['error'],
                'details' => $this->formatLogDetails($row['details'], $row['log_data']),
            ];
        }

        return $errors;
    }

    protected function tearDown(): void
    {
        // clean up GLOBALS left behind by previous tests
        unset($GLOBALS['LANG']);
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }
}
