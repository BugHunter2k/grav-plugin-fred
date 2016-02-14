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
                'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
                'onPageProcessed' =>  ['onPageProcessed', 0],
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
        if (
            $this->grav['uri']->basename() == "upload-image.json"
            && !empty($_POST) 
            && $this->enabled
        ) {
            $this->addFile();
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
    * Insert editor div around the active article
    * Check for the right item and modify the content
    * 
    * @param Event 
    */
    public function onPageProcessed($e)
    {
        $page = $e->offsetGet('page');
        if ($page->isPage() && $page->route() == $this->grav['uri']->path() ) {
            $page->content('<div data-editable="true" data-name="blog_item">'.$page->content().'</div>');
            $this->grav['debugger']->addMessage("Adding Editor-<div>");
        }
        return;
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
    * @return void - calls ajaxoutput
    */
    public function savePage() {
    
        $user = $this->grav['user'];
        
        // Check Permissions for Save
        if ($user->authenticated && $user->authorize("site.editor")) {
            $page = $this->getPage("uri");
            
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
    
    /**
    * Handles files upload 
    * Triggerd vie upload-image.json 
    * 
    * @param void
    * @return void - creates json - output for ajax
    */
    public function addFile() {
        $user = $this->grav['user'];
        
        // Check Permissions for Save
        if ($user->authenticated && $user->authorize("site.editor")) {
            $page = $this->getPage("uri");

            /** @var Config $config */
            $config = $this->grav['config'];
            if (!isset($_FILES['file']['error']) || is_array($_FILES['file']['error'])) {
                $this->json_response = ['status' => 'error', 'message' => "Invalid Parameters"];
                return false;
            }
            // Check $_FILES['file']['error'] value.
            switch ($_FILES['file']['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->json_response = ['status' => 'error', 'message' => "No file error"];
                    return false;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $this->json_response = ['status' => 'error', 'message' => "Filesize error"];
                    return false;
                default:
                    $this->json_response = ['status' => 'error', 'message' => "Unkown error"];
                    return false;
            }
            $grav_limit = $config->get('system.media.upload_limit', 0);
            // You should also check filesize here.
            if ($grav_limit > 0 && $_FILES['file']['size'] > $grav_limit) {
                $this->json_response = ['status' => 'error', 'message' => "File to big"];
                return false;
            }
            // Check extension
            $fileParts = pathinfo($_FILES['file']['name']);
            $fileExt = '';
            if (isset($fileParts['extension'])) {
                $fileExt = strtolower($fileParts['extension']);
            }
            // If not a supported type, return
            if (!$fileExt || !$config->get("media.{$fileExt}")) {
                $this->json_response = ['status' => 'error', 'message' => 'Invalid filetype: '.$fileExt];
                return false;
            }
            // Upload it
            $imagefile = sprintf('%s/%s', $page->path(), $_FILES['file']['name']);
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $imagefile)) {
                $this->json_response = ['status' => 'error', 'message' => 'Failed to move file'];
                return false;
            }
            // Get image Size
            $size = getimagesize($imagefile);
            $this->json_response = ['status' => 'success', 'url' => sprintf('%s/%s', $page->route(), $_FILES['file']['name']), 'size'=> [$size[0], $size[1]] , 'message' => 'Upload successfull'];
            
            echo json_encode($this->json_response);
            die;
        }    
    }
    
    /**
    * Get page from POST parameter
    *
    * @param string POST field to get URI from 
    * @return Page object
    */
    protected function getPage($param) {
        // Get pages object and initialize
        $pages = $this->grav['pages'];
        $pages->init();
        
        // Filter uri from parameters
        $uri_string = filter_input(INPUT_POST, $param, FILTER_SANITIZE_SPECIAL_CHARS);
        // Parse uri to get the plain path 
        $uri = parse_url($uri_string);
        
        // get the page connected with $uri.path
        $page = $pages->dispatch($uri['path']);
        return $page;
    }
}
