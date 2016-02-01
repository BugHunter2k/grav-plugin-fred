<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page;
use Grav\Common\Twig\Twig;
use Grav\Common\User\User;

/**
* Grav Plugin Fred 
* provides a frontend editor on pages
* 
* this is an early alpha!!
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
                'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
                'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0]
            ]);
            // Save plugin stauts 
            $this->enabled = true;
        } else {
            $this->enabled = false;
        }

        if (
            $this->grav['uri']->basename() == "fredsave.json"
            && !empty($_POST) 
            && $this->enabled
        ) {
            $this->savePage();
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
                    'plugin://fred/js/to-markdown.js',
                    'plugin://fred/js/fred.js',
            );
            // register and add assets
            $assets = $this->grav['assets'];
            $assets->registerCollection('fred', $fredbits);
            $assets->add('fred', 100);
        }
    }
    /**
    * register plugin template
    */
    public function onTwigTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }
    
    /**
    * Get Page informations 
    * update content
    * save page
    * 
    * @param Page Page that has to be saved
    * @return void - calls ajaxoutput
    */
    public function savePage() {
    
        $user = $this->grav['user'];
        
        // Check Permissions for Save
        if ($user->authenticated && $user->authorize("site.editor")) {
            // Get pages object and initialize
            $pages = $this->grav['pages'];
            $pages->init();
            
            // Filter uri from parameters
            $uri_string = filter_input(INPUT_POST, "uri", FILTER_SANITIZE_SPECIAL_CHARS);
            // Parse uri to get the plain path 
            $uri = parse_url($uri_string);
            
            // get the page connected with $uri.path
            $page = $pages->dispatch($uri['path']);

            // get changes
            // TODO preapre multipage content
            $blogItem = filter_input(INPUT_POST, "blog_item", FILTER_SANITIZE_SPECIAL_CHARS);
            
            // Get correct linebreaks 
            $blogItem = html_entity_decode($blogItem, ENT_QUOTES, 'UTF-8');
            
            // set content and save page
            if (!empty($blogItem)) {
                $page->rawMarkdown((string) $blogItem);
                $page->save();
            }
            // Set results json
            $this->json_response = ['status' => 'success', 'message' => 'Your changes has been saved'];
        } else {
            // Non valid users should not change the article
            $this->json_response = ['status' => 'unauthorized', 'message' => 'You have insufficient permissions for editing. Make sure you logged in.'];
        }

        echo json_encode($this->json_response);
        die;
    }
    
    
    
}
