<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\User\User;

/**
* Grav Plugin Fred 
* provides a frontend editor on pages
* 
* @author Ingo Hollmann
* @link https://github.com/BugHunter2k/fred
* @license http://opensource.org/licenses/MIT
* 
*/
class FredPlugin extends Plugin
{

    protected $enabled=false;
    
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        // initialize when plugins are ready
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

    /**
    * Initialize Fred 
    * Check for user-auth and access befor 
    * loading js an css etc.
    */
    public function initializeFred() {
        // Check for required plugins
        if (!$this->grav['config']->get('plugins.login.enabled') ||
            !$this->grav['config']->get('plugins.form.enabled') ) {
            throw new \RuntimeException('One of the required plugins is missing or not enabled');
        }
        
        // Check on logged in user and authorization for page editing
        $user = $this->grav['user'];
        if ($user->authenticated && $user->authorize("site.editor")) {
            $this->enable([
                'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
            ]);
            // Save plugin stauts 
            $this->enabled = true;
        } else {
            $this->enabled = false;
        }

    }

    /**
     * if enabled on this page, load the JS + CSS and set the selectors.
     */
    public function onTwigSiteVariables()
    {
        // check if enabled
        if ($this->enabled) {
            // List of js and css files needed for contenttools
            $fredbits = array(
                    'plugin://fred/css/content-tools.min.css',
                    'plugin://fred/js/content-tools.min.js',
                    'plugin://fred/js/editor.js',
            );
            // register and add assets
            $assets = $this->grav['assets'];
            $assets->registerCollection('fred', $fredbits);
            $assets->add('fred', 100);
        }
    }
}
