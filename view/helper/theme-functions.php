<?php
/**
 * @var \Laminas\View\Renderer\PhpRenderer $this
 */

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Service\Delegator\SitePaginatorDelegatorFactory;
use Omeka\Site\Theme\Manager;
use Omeka\Site\Theme\Theme;
use Laminas\I18n\Translator\TranslatorInterface as TranslatorInterface;
use Laminas\View\Renderer\PhpRenderer;

function prepareSite(PhpRenderer $view): ?SiteRepresentation
{
    $plugins = $view->getHelperPluginManager();
    $api = $plugins->get('api');
    $setting = $plugins->get('setting');
    $params = $plugins->get('params');

    $defaultSiteSlug = 'transcription';

    $services = $api->read('vocabularies', ['id' => 1])->getContent()->getServiceLocator();
    $site = $api->searchOne('sites', ['slug' => $params->fromRoute('site-slug') ?: $defaultSiteSlug])->getContent()
        ?: $api->read('sites', ['id' => $setting('default_site')])->getContent();

    /**
     * @see \Omeka\Mvc\MvcListeners::prepareSite()
     * @see \Omeka\Mvc\MvcListeners::preparePublicSite()
     */

    // Inject the site into things that need it.
    $services->get('Omeka\Settings\Site')->setTargetId($site->id());
    $services->get('ControllerPluginManager')->get('currentSite')->setSite($site);
    $services->get('ViewHelperManager')->get('currentSite')->setSite($site);

    // Set the site to the top level view model
    // $event->getViewModel()->site = $site;
    $view->site = $site;

    // Set the current theme for this site.
    $themeManager = $services->get('Omeka\Site\ThemeManager');
    $currentTheme = $themeManager->getTheme($site->theme());
    if (!$currentTheme) {
        $currentTheme = new Theme('not_found');
        $currentTheme->setState(Manager::STATE_NOT_FOUND);
    }
    $themeManager->setCurrentTheme($currentTheme);

    $hasTranslations = $currentTheme->getIni('has_translations');
    if ($hasTranslations) {
        $translator = $services->get(TranslatorInterface::class);
        $translator->getDelegatedTranslator()->addTranslationFilePattern(
            'gettext',
            $currentTheme->getPath('language'),
            '%s.mo'
        );
    }

    $services->addDelegator('Omeka\Paginator', SitePaginatorDelegatorFactory::class);

    // Add the theme view templates to the path stack.
    $services->get('ViewTemplatePathStack')->addPath($currentTheme->getPath('view'));

    // Load theme view helpers on-demand.
    $helpers = $currentTheme->getIni('helpers');
    if (is_array($helpers)) {
        foreach ($helpers as $helper) {
            $factory = function ($pluginManager) use ($site, $helper, $currentTheme) {
                require_once $currentTheme->getPath('helper', "$helper.php");
                $helperClass = sprintf('\OmekaTheme\Helper\%s', $helper);
                return new $helperClass;
            };
            $services->get('ViewHelperManager')->setFactory($helper, $factory);
        }
    }

    // Set the runtime locale and translator language to the configured site
    // locale.
    $locale = $services->get('Omeka\Settings\Site')->get('locale');
    if ($locale) {
        if (extension_loaded('intl')) {
            \Locale::setDefault($locale);
        }
        $services->get('MvcTranslator')->getDelegatedTranslator()->setLocale($locale);
    }

    return $site;
}
