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
            !$this->grav['config']->get('plugins.form.enabled') ||
            !$this->grav['config']->get('plugins.admin.enabled') ) {
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
                    'plugin://fred/js/fred.js',
            );
            // register and add assets
            $assets = $this->grav['assets'];
            $assets->registerCollection('fred', $fredbits);
            $assets->add('fred', 100);
        }
    }
    
    /**
    * Get Page informations 
    * update content
    * save page
    * 
    * @param Page Page that has to be saved
    * @return void - calls ajaxoutput
    */
    public function savePage(\Grav\Common\Page\Page $page) {
        // get local names for some objects
        $input = $this->post;
        $user = $this->grav['user'];
        
        // Check Permissions for Save
        if ($user->authenticated && $user->authorize("site.editor")) {
            var_dump($input);
            // Fill content last because it also renders the output.
            if (isset($input['content'])) {
                $page->rawMarkdown((string) $input['content']);
            }
            
        } else {
            $this->json_response = ['status' => 'unauthorized', 'message' => 'You have insufficient permissions for editing. Make sure you logged in.'];
            return;
        } 
    }
    
    
    
}
