<?php namespace Contentify\Controllers;

use ModelHandler, Str, Sentry, OpenGraph, Request, Session, View, Controller, Exception;

abstract class BaseController extends Controller {

    /**
     * The name of the module
     * @var string
     */
    protected $moduleName = '';

    /**
     * The name of the controller (without class path)
     * @var string
     */
    protected $controllerName = '';

    /**
     * The name of the model (without class path)
     * @var string
     */
    protected $modelName = '';

    /**
     * The name of the model (with class path)
     * @var string
     */
    protected $modelClass = '';

    /**
     * The name of the form template (for CRUD auto handling)
     * @var string
     */
    protected $formTemplate = '';

    public function __construct()
    {
        /*
         * Save module and controller name
         */
        $className              = get_class($this);
        $this->moduleName       = explode('\\', $className)[2];
        $className              = class_basename($className);
        $this->controllerName   = str_replace(['Admin', 'Controller'], '', $className);

        /*
         * Set model full name
         */
        if (! $this->modelClass) {
            if (str_contains($this->modelName, '\\')) {
                $this->modelClass = $this->modelName;
            } else {
                $this->modelClass = 'App\Modules\\'.$this->moduleName.'\\'.$this->modelName;
            }
        }

        /*
         * Set CRUD form template name
         */
        if (! $this->formTemplate) {
            if ($this->moduleName === str_plural($this->modelName)) {
                $this->formTemplate = 'form';
            } else {
                // If modelname & modulename differ, the form name should be e. g. "users_form":
                $this->formTemplate = strtolower($this->controllerName).'_form'; 
            }
            if (starts_with(strtolower($className), 'admin')) $this->formTemplate = 'admin_'.$this->formTemplate;
        }

        /*
         * Enable auto CSRF protection
         */ 
        $this->middleware('csrf');
    }

    /**
     * Execute an action on the controller.
     * (This overrides a method of the Illuminate BaseController.)
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        $this->setupLayout();

        $response = call_user_func_array(array($this, $method), $parameters);

        // If no response is returned from the controller action and a layout is being
        // used we will assume we want to just return the layout view as any nested
        // views were probably bound on this view during this controller actions.
        if (is_null($response) && ! is_null($this->layout))
        {
            $response = $this->layout;
        }

        return $response;
    }

    /**
     * Getter for $moduleName
     * 
     * @return string
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * Getter for $controllerName
     * 
     * @return string
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * Getter for $modelName
     * 
     * @return string
     */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * Getter for $modelClass
     * 
     * @return string
     */
    public function getModelClass()
    {
        return $this->modelClass;
    }

    /**
     * Getter for $formTemplate
     * 
     * @return string
     */
    public function getFormTemplate()
    {
        return $this->formTemplate;
    }

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout()
    {
        if ( ! is_null($this->layout))
        {
            $this->layout                   = View::make($this->layout);
            $this->layout->page             = null;
            $this->layout->metaTags         = [];
            $this->layout->openGraph        = null;
            $this->layout->title            = null;
            $this->layout->breadcrumb       = [trans('app.home') => route('home'), $this->moduleName => null];
            $this->layout->templateClass    = null;
        }
    }

    /**
     * Shortcut for $this->layout->nest(): Adds a view to the main layout.
     *
     * @param string $template Name of the template
     * @param array  $data     Array with data passed to the compile engine
     * @param bool   $replace  Replace the output already added
     * @return void
     */
    public function pageView($template = '', $data = array(), $replace = false)
    {
        if ($this->layout != null) {
            if ($replace or $this->layout->page == null) {
                $this->layout->page = View::make($template, $data);
            } else {
                $this->layout->page .= View::make($template, $data)->render();
            }

            $this->layout->templateClass = 'page-'.Str::slug(str_replace('::', '-', $template));
        } else {
            throw new Exception('Error: Controller layout is null!');
        }
    }

    /**
     * Shortcut for $this->layout->nest(): Adds a string to the main layout.
     *
     * @param string $output  HTML or text to output on the template.
     * @param bool   $replace Replace the output already added
     * @return void
     */
    public function pageOutput($output, $replace = false)
    {
        if ($this->layout != null) {
            if ($replace) {
                $this->layout->page = $output;
            } else {
                $this->layout->page .= $output;
            }
        } else {
            throw new Exception('Error: $this->layout is null!');
        }
    }

    /**
     * Adds an alert view to the main layout.
     *
     * @param string $type  The type (error, info, warning, success)
     * @param string $title The title
     * @param string $text  Optional text
     * @return void
     */
    public function alert($type, $title, $text = '')
    {
        if ($this->layout != null) {
            $this->layout->page .= View::make('alert', ['type' => 'info', 'title' => $title, 'text' => $text]);
        } else {
            throw new Exception('Error: $this->layout is null!');
        }
    }

    /**
     * Adds an success alert view to the main layout.
     *
     * @param string $title The title
     * @param string $text  Optional text
     * @return void
     */
    public function alertSuccess($title, $text = '')
    {
        $this->alert('success', $title, $text);
    }

    /**
     * Adds a warning alert view to the main layout.
     *
     * @param string $title The title
     * @param string $text  Optional text
     * @return void
     */
    public function alertWarning($title, $text = '')
    {
        $this->alert('warning', $title, $text);
    }

    /**
     * Adds an error (danger) alert view to the main layout.
     *
     * @param string $title The title
     * @param string $text  Optional text
     * @return void
     */
    public function alertError($title, $text = '')
    {
        $this->alert('danger', $title, $text);
    }

    /**
     * Adds an info alert view to the main layout.
     *
     * @param string $title The title
     * @param string $text  Optional text
     * @return void
     */
    public function alertInfo($title, $text = '')
    {
        $this->alert('info', $title, $text);
    }

    /**
     * Inserts a flash alert to the main layout.
     * The type is 'info'.
     *
     * @param string $title
     * @return void
     */
    public function alertFlash($title)
    {
        Session::flash('_alert', $title);
    }

    /**
     * Adds a meta tag to the variables of the main layout.
     * Use HTML::metaTags() to render them.
     *
     * @param string $template Name of the meta tag
     * @param string $content  Content of the meta tag
     * @return void
     */
    public function metaTag($name, $content)
    {
        if ($this->layout != null) {
            $this->layout->metaTags[$name] = $content;
        } else {
            throw new Exception('Error: Controller layout is null!');
        }
    }

    /**
     * Sets the title tag for this layout. It's passed as a variable to the template.
     * Use HTML::title() to render it.
     *
     * @param string $title The title
     * @return void
     */
    public function title($title)
    {
        if ($this->layout != null) {
            $this->layout->title = $title;
        } else {
            throw new Exception('Error: Controller layout is null!');
        }
    }

    /**
     * Binds an OpenGraph instance to this layout. The instance is passed as a variable to the template.
     * Use HTML::openGraphTags() to render the tags.
     *
     * @param OpenGraph $openGraph OpenGraph instance
     * @return void
     */
    public function openGraph(OpenGraph $openGraph)
    {
        if ($this->layout != null) {
            $this->layout->openGraph = $openGraph;
        } else {
            throw new Exception('Error: Controller layout is null!');
        }
    }

    /**
     * Sets the links for the breadcrumb navigation.
     * Use the Navigations::Breadcrumb widget to render the breadcrumb navi.
     *
     * @param array $links Array with items of title (key) and URLs (link)
     * @return void
     */
    public function breadcrumb($links = array())
    {
        if ($this->layout != null) {
            $this->layout->breadcrumb = $links;
        } else {
            throw new Exception('Error: Controller layout is null!');
        }
    }

    /**
     * Generates an index page from a model and $data
     * 
     * @param  array  $data             Array with information how to build the form. See $defaults for details.
     * @param  string $userInterface    Frontend ("front") or backend ("admin")?
     * @return void
     */
    public function indexPage($data, $userInterface = 'admin')
    {
        ModelHandler::controller($this);

        ModelHandler::index($data, $userInterface);
    }

    /**
     * Returns true if the current user has read access to the module.
     * 
     * @return boolean
     */
    public function hasAccessRead() 
    {
        return (user() and user()->hasAccess(strtolower($this->moduleName), PERM_READ));
    }

    /**
     * Returns true if the current user has create access to the module.
     * 
     * @return boolean
     */
    public function hasAccessCreate() 
    {
        return (user() and user()->hasAccess(strtolower($this->moduleName), PERM_CREATE));
    }

    /**
     * Returns true if the current user has update access to the module.
     * 
     * @return boolean
     */
    public function hasAccessUpdate() 
    {
        return (user() and user()->hasAccess(strtolower($this->moduleName), PERM_UPDATE));
    }

    /**
     * Returns true if the current user has delete access to the module.
     * 
     * @return boolean
     */
    public function hasAccessDelete() 
    {
        return (user() and user()->hasAccess(strtolower($this->moduleName), PERM_DELETE));
    }

    /**
     * Returns true if the current user has read access to the module.
     * If not an alert will be set.
     * 
     * @return bool
     */
    public function checkAccessRead()
    {
        if ($this->hasAccessRead()) {
            return true;
        } else {
            if (! Request::ajax()) {
                $this->alertError(trans('app.access_denied'));
            }

            return false;
        }
    }

    /**
     * Returns true if the current user has create access to the module.
     * If not an alert will be set.
     * 
     * @return bool
     */
    public function checkAccessCreate()
    {
        if ($this->hasAccessCreate()) {
            return true;
        } else {
            if (! Request::ajax()) {
                $this->alertError(trans('app.access_denied'));
            }

            return false;
        }
    }

    /**
     * Returns true if the current user has update access to the module.
     * If not an alert will be set.
     * 
     * @return bool
     */
    public function checkAccessUpdate()
    {
        if ($this->hasAccessUpdate()) {
            return true;
        } else {
            if (! Request::ajax()) {
                $this->alertError(trans('app.access_denied'));
            }

            return false;
        }
    }

    /**
     * Returns true if the current user has delete access to the module.
     * If not an alert will be set.
     * 
     * @return bool
     */
    public function checkAccessDelete()
    {
        if ($this->hasAccessDelete()) {
            return true;
        } else {
            if (! Request::ajax()) {
                $this->alertError(trans('app.access_denied'));
            }
            
            return false;
        }
    }

    /**
     * Returns true if the current user is authenticated.
     * If not an alert will be set.
     * 
     * @return bool
     */
    public function checkAuth()
    {
        if (Sentry::check()) {
            return true;
        } else {
            $this->alertError(trans('app.no_auth'));
            return false;
        }
    }
    
}