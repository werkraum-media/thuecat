<?php

declare(strict_types=1);

namespace WerkraumMedia\ThueCat;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use WerkraumMedia\ThueCat\DependencyInjection\ConverterPass;
use WerkraumMedia\ThueCat\DependencyInjection\EntityPass;
use WerkraumMedia\ThueCat\DependencyInjection\UrlProvidersPass;
use WerkraumMedia\ThueCat\Domain\Import\Entity\MapsToType;
use WerkraumMedia\ThueCat\Domain\Import\Typo3Converter\Converter;
use WerkraumMedia\ThueCat\Domain\Import\UrlProvider\UrlProvider;

return function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerForAutoconfiguration(UrlProvider::class)->addTag(UrlProvidersPass::TAG);
    $containerBuilder->addCompilerPass(new UrlProvidersPass());

    $containerBuilder->registerForAutoconfiguration(Converter::class)->addTag(ConverterPass::TAG);
    $containerBuilder->addCompilerPass(new ConverterPass());

    $containerBuilder->registerForAutoconfiguration(MapsToType::class)->addTag(EntityPass::TAG);
    $containerBuilder->addCompilerPass(new EntityPass());
};
