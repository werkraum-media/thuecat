<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use WerkraumMedia\ThueCat\Domain\Import\Converter\Converter;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\UrlProvider;

return function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(UrlProvider::class)->addTag(DependencyInjection\UrlProvidersPass::TAG);
    $containerBuilder->addCompilerPass(new DependencyInjection\UrlProvidersPass());

    $containerBuilder->registerForAutoconfiguration(Converter::class)->addTag(DependencyInjection\ConverterPass::TAG);
    $containerBuilder->addCompilerPass(new DependencyInjection\ConverterPass());
};
