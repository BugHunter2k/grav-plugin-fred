<?php
namespace Grav\Plugin;

Use Grav\Common\Plugin;
use \Grav\Common\Grav;


class FredPlugin extends Plugin
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onThemeInitialized' => ['onThemeInitialized', 0]
        ];
    }


    /**
     * Initialize configuration
     */
    public function onThemeInitialized()
    {
        $this->enable([
                'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
        ]);
    }


    /**
     * if enabled on this page, load the JS + CSS and set the selectors.
     */
    public function onTwigSiteVariables()
    {
          $fredbits = array(
          	'plugin://fred/css/content-tools.min.css',
          	'plugin://fred/js/content-tools.min.js',
          	'plugin://fred/js/editor.js',
	);
        $assets = $this->grav['assets'];
        $assets->registerCollection('fred', $fredbits);
        $assets->add('fred', 100);
    }
}
