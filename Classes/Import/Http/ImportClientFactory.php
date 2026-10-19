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

namespace WerkraumMedia\ThueCat\Import\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use WerkraumMedia\ThueCat\Import\Settings\ImportSetting;
use WerkraumMedia\ThueCat\Import\Settings\ImportSettings;

/**
 * The import's only source of HTTP clients; its consumers are excluded from
 * autowiring so the container's unbounded client stays unreachable.
 *
 * Core ships timeout => 0 (unlimited), which is what lets one stalled host hang
 * a run. PSR-18 takes no per-request options, so the bound lives in the client.
 */
class ImportClientFactory
{
    public function __construct(
        protected readonly ImportSettings $settings,
        protected readonly RetryTally $tally,
        protected readonly LoggerInterface $logger,
        protected readonly RequestLedger $ledger,
    ) {
    }

    /**
     * @param ApplicationContext|null $context Defaults to the running context; tests pass one in.
     */
    public function build(ClientBudget $budget, ?ApplicationContext $context = null): ImportHttpClient
    {
        $context ??= Environment::getContext();

        $confVars = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        $configured = is_array($confVars) ? ($confVars['HTTP'] ?? []) : [];
        /** @var array<string, mixed> $options */
        $options = is_array($configured) ? $configured : [];
        $handlers = $options['handler'] ?? null;
        unset($options['allowed_hosts'], $options['handler']);

        $options['verify'] = filter_var($options['verify'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ?? $options['verify'];
        $options['timeout'] = $this->settings->resolve(ImportSetting::ReadTimeout, $budget->readTimeout);
        $options['connect_timeout'] = $this->settings->resolve(ImportSetting::ConnectTimeout, $budget->connectTimeout);
        // 4xx/5xx must return a response so the status can be inspected.
        $options['http_errors'] = false;
        $options['handler'] = $this->handlerStack($handlers);

        $client = new RetryingClient(
            new Client($options),
            $this->settings->resolve(ImportSetting::MaxAttempts, $budget->maxAttempts),
            null,
            $this->tally
        );

        // Outside the retries: one entry per final answer, not per attempt.
        $client = new RequestCountingClient($client, $this->ledger);
        return $context->isDevelopment() ? new DeniedRequestLoggingClient($client, $this->logger) : $client;
    }

    /**
     * Dropping these would lose installation middlewares and the suite's faker.
     *
     * @param mixed $handlers
     */
    protected function handlerStack($handlers): HandlerStack
    {
        if ($handlers instanceof HandlerStack) {
            return $handlers;
        }

        $stack = HandlerStack::create();
        if (is_array($handlers)) {
            foreach ($handlers as $name => $handler) {
                if (is_callable($handler)) {
                    $stack->push($handler, (string)$name);
                }
            }
        }

        return $stack;
    }
}
