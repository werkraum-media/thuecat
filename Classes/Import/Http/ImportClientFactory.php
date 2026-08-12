<?php

declare(strict_types=1);

/*
 * Copyright (C) 2026 werkraum-media
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 */

namespace WerkraumMedia\ThueCat\Import\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
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
        protected readonly RetryTally $tally
    ) {
    }

    public function build(ClientBudget $budget): ImportHttpClient
    {
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

        return new RetryingClient(
            new Client($options),
            $this->settings->resolve(ImportSetting::MaxAttempts, $budget->maxAttempts),
            null,
            $this->tally
        );
    }

    /**
     * Dropping these would lose installation middlewares and the suite's faker.
     *
     * @param mixed $handlers
     */
    private function handlerStack($handlers): HandlerStack
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
