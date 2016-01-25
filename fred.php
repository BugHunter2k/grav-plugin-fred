<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\User\User;


class FredPlugin extends Plugin
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }


    /**
     * Initialize configuration
     */
    public function onPluginsInitialized()
    {
        // Don't load in Admin-Backend
        if ($this->isAdmin()) {
            $this->active = false;
            return;
        }
        $this->initializeFred();
    }

    public function initializeFred() {
        // Check for required plugins
        if (!$this->grav['config']->get('plugins.login.enabled') ||
            !$this->grav['config']->get('plugins.form.enabled') ) {
            throw new \RuntimeException('One of the required plugins is missing or not enabled');
        }
        
        $user = $this->grav['user'];
        $login = $this->grav;
        // Check on logged in user and authorization for page editing
        if ($user->authenticated && $user->authorize("site.editor")) {
            $this->enable([
                'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
            ]);
        } else {
        }

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
