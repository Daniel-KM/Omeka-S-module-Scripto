<?php declare(strict_types=1);
namespace Scripto\Service\Mediawiki;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Scripto\Mediawiki\ApiClient;

class ApiClientFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        return new ApiClient(
            // Decrease the chance of timeout by increasing to 20 seconds, which
            // is between Omeka's default (10) and MediaWiki's default (25).
            $services->get('Omeka\HttpClient')->setOptions(['timeout' => 20]),
            $settings->get('scripto_apiurl'),
            $settings->get('time_zone', 'UTC')
        );
    }
}
