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

namespace WerkraumMedia\ThueCat\Tests\Functional;

use Codappix\Typo3PhpDatasets\TestingFramework;
use DateTimeImmutable;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

abstract class AbstractImportTestCase extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    use TestingFramework;

    /**
     * Whether to expect errors to be logged.
     * Will check for no errors if set to false.
     */
    protected bool $expectErrors = false;

    /**
     * Default domain used by expectFetch()/expectNotFound() when no
     * per-call override is supplied. Subclasses can change this in setUp()
     * (or as a default value) to point at a different upstream host.
     */
    protected string $fixtureDomain = 'thuecat.org';

    /**
     * URL path segment between the domain and the resource id, e.g.
     * "resources" → https://thuecat.org/resources/<id>. Subclasses can
     * override per file.
     */
    protected string $fixturePath = 'resources';

    /**
     * Filesystem root that mirrors the URL hierarchy; expectFetch()
     * looks up `<base>/<domain>/<path>/<filename>`. Defaults to the shared
     * import-tests fixture tree; subclasses with their own tree override.
     */
    protected string $fixtureGuzzleBase = __DIR__ . '/Fixtures/Import/Guzzle';

    protected array $coreExtensionsToLoad = [
        'core',
        'backend',
        'extbase',
        'frontend',
        'fluid_styled_content',
        'install',
        'filelist',
        'filemetadata',
    ];
    protected array $testExtensionsToLoad = [
        'werkraummedia/thuecat/',
    ];
    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/thuecat/Tests/Functional/Fixtures/Import/Sites/' => 'typo3conf/sites',
    ];
    protected array $configurationToUseInTestInstance = [
        'LOG' => [
            'WerkraumMedia' => [
                'writerConfiguration' => [
                    \TYPO3\CMS\Core\Log\LogLevel::ERROR => [
                        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                            'logFileInfix' => 'error',
                        ],
                    ],
                ],
            ],
        ],
        'EXTENSIONS' => [
            'thuecat' => [
                'apiKey' => null,
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        GuzzleClientFaker::registerClient();
        $this->importPHPDataSet(__DIR__ . '/Fixtures/Import/BackendUser.php');
        $this->setDateAspect(new DateTimeImmutable('2024-09-19T00:00:00+00:00'));
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->create('en_US');
        foreach ($this->getLogFiles() as $logFile) {
            file_put_contents($logFile, '');
        }
    }

    protected function assertPostConditions(): void
    {
        if ($this->expectErrors === true) {
            return;
        }
        foreach ($this->getLogFiles() as $file) {
            self::assertSame(
                '',
                file_get_contents($file),
                'The TYPO3 log file "' . $file . '" contained content while expecting to be empty.'
            );
        }
    }

    protected function tearDown(): void
    {
        $this->expectErrors = false;
        unset($GLOBALS['LANG']);
        $remaining = GuzzleClientFaker::tearDown();
        parent::tearDown();
        if ($remaining !== []) {
            $lines = ['Test expected HTTP fetches that never happened:'];
            foreach ($remaining as $url => $labels) {
                $lines[] = sprintf('  %s  ×%d  [%s]', $url, count($labels), implode(', ', $labels));
            }
            self::fail(implode("\n", $lines));
        }
    }

    /**
     * Stage one expected fetch. The fixture file lives at
     * `<fixtureGuzzleBase>/<fixtureDomain>/<fixturePath>/<filename>`; the
     * matching URL is `https://<fixtureDomain>/<fixturePath>/<basename-no-ext>`.
     * Stack the same call N times to declare N expected fetches of that URL —
     * a single excess fetch trips the empty-bag error and a single missing
     * fetch trips the tearDown leftover assertion.
     */
    protected function expectFetch(string $filename): void
    {
        $this->expectFetchAt($this->fixtureDomain, $this->fixturePath, $filename);
    }

    protected function expectFetchAt(string $domain, string $path, string $filename): void
    {
        $segment = pathinfo($filename, PATHINFO_FILENAME);
        $url = sprintf('https://%s/%s/%s', $domain, $path, $segment);
        $file = $this->fixtureGuzzleBase . '/' . $domain . '/' . $path . '/' . $filename;
        GuzzleClientFaker::expectFileForUrl($url, $file);
    }

    /**
     * Stage one expected fetch for a URL that doesn't fit the
     * `<domain>/<path>/<segment>` convention (e.g. endpoints with query
     * strings). The fixture file path is taken verbatim relative to
     * `$fixtureGuzzleBase`.
     */
    protected function expectFetchForUrl(string $url, string $fixtureRelativePath): void
    {
        $file = $this->fixtureGuzzleBase . '/' . ltrim($fixtureRelativePath, '/');
        GuzzleClientFaker::expectFileForUrl($url, $file);
    }

    protected function expectNotFound(string $segment): void
    {
        $this->expectNotFoundAt($this->fixtureDomain, $this->fixturePath, $segment);
    }

    protected function expectNotFoundAt(string $domain, string $path, string $segment): void
    {
        $url = sprintf('https://%s/%s/%s', $domain, $path, $segment);
        GuzzleClientFaker::expectNotFoundForUrl($url);
    }

    /**
     * @return string[]
     */
    private function getLogFiles(): array
    {
        return [
            self::getInstancePath() . '/typo3temp/var/log/typo3_0493d91d8e.log',
            $this->getErrorLogFile(),
        ];
    }

    protected function getErrorLogFile(): string
    {
        return self::getInstancePath() . '/typo3temp/var/log/typo3_error_0493d91d8e.log';
    }

    /**
     * @api Actual tests can use this method to define the actual date of "now".
     */
    protected function setDateAspect(DateTimeImmutable $dateTime): void
    {
        $this->getContainer()
            ->get(Context::class)
            ->setAspect(
                'date',
                new DateTimeAspect($dateTime)
            )
        ;
    }

    /**
     * Workaround ConfigurationManager requiring request
     */
    protected function workaroundExtbaseConfiguration(): void
    {
        $fakeRequest = new ServerRequest();
        $fakeRequest = $fakeRequest->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $this->get(ConfigurationManagerInterface::class)
            ->setRequest($fakeRequest)
        ;
    }
}
