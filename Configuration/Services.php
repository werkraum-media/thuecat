<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Converter;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\UrlProvider;

return function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(UrlProvider::class)->addTag('thuecat.urlprovider');
    $containerBuilder->addCompilerPass(new DependencyInjection\UrlProvidersPass('thuecat.urlprovider'));

    $containerBuilder->registerForAutoconfiguration(Converter::class)->addTag('thuecat.converter');
    $containerBuilder->addCompilerPass(new DependencyInjection\ConverterPass('thuecat.converter'));
};
