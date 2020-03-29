<?php

namespace Mavericks\ResourceCustomButton;

use Laravel\Nova\Card;
use Laravel\Nova\Resource;
use Mavericks\ResourceCustomButton\Actions\Action;

class ResourceCustomButton extends Card
{
    /**
     * The width of the card (1/3, 1/2, or full).
     *
     * @var string
     */
    public $width = 'full';

    /**
     * The width of the card (1/3, 1/2, or full).
     *
     * @var string
     */
    protected $buttonText = 'Custom Button';

    /**
     * Get action to be performed on the resource
     *
     * @var string
     */
    protected $action = null;

    /**
     * Get action to be performed on the resource
     *
     * @var string
     */
    protected $link = null;

    /**
     * Get action to be performed on the resource
     *
     * @var string
     */
    protected $route = null;

    /**
     * If it is a route type
     *
     * @var bool
     */
    protected $isRoute = false;

    /**
     * If it is a link type
     *
     * @var bool
     */
    protected $isLink = false;

    /**
     * If the user wants to display global fields
     *
     * @var bool
     */
    protected $withGlobalFields = true;

    /**
     * Used to set the text of the button
     *
     * @param string $buttonText
     * @return ResourceCustomButton
     */
    public function setButtonText ( string $buttonText ) : object
    {
        $this->buttonText = $buttonText;

        return $this;
    }

    /**
     * Get the action to be triggered
     *
     * @param string $namespace
     * @return $this
     */
    public function action(string $namespace) : object
    {
        $this->action = $this->retrieveActionUri($namespace);

        return $this;
    }

    /**
     * Gets the link
     *
     * @param string $href
     * @param string $target
     * @return object
     */
    public function link(string $href, string $target = '_blank') : object
    {
        $this->isLink = true;
        $this->isRoute = false;
        $this->action = null;
        $this->route = null;

        $this->link = compact('href', 'target');

        return $this;
    }

    /**
     * Gets the route
     *
     * @param string $route
     * @param string $namespace
     * @param int|null $id
     * @param null $lensKey
     * @param int $page
     * @return $this
     */
    public function route(string $route, string $namespace, int $id = null, $lensKey = null, $page = 1) : object
    {
        $this->isRoute = true;
        $this->isLink = false;
        $this->action = null;
        $this->link = null;

        $this->resolveRoute($route, $this->validateParam($route, $namespace, $id, $lensKey), $page);

        return $this;
    }

    /**
     * Retrieve the uri key of the resource provided
     *
     * @param string $namespace
     * @return string
     */
    protected function retrieveResourceUri(string $namespace) : string
    {
        return class_exists($namespace) && is_subclass_of($namespace, Resource::class)
            ?  (new $namespace($namespace))->uriKey() : $namespace;
    }

    /**
     * Validate param passed to route
     *
     * @param $route
     * @param $namespace
     * @param $id
     * @param $lensKey
     * @return array |null
     */
    private function validateParam($route, $namespace,  $id, $lensKey)
    {
        $param = [
            'resourceName' => $this->retrieveResourceUri($namespace),
        ];

        if($route === 'lens')
            $param = array_merge($param, [
                'lens' => $lensKey
            ]);

        if($route === 'edit' || $route === 'detail')
            $param = array_merge($param, [
                'resourceId'   => $id,
            ]);

        return $param;
    }

    /**
     * Retrieve the uri key of the action provided
     *
     * @param string $namespace
     * @return string
     */
    protected function retrieveActionUri(string $namespace) :string
    {
        return class_exists($namespace) && is_subclass_of($namespace, Action::class)
            ? (new $namespace())->uriKey() : $namespace;
    }

    /**
     * Used for resolving route
     *
     * @param $name
     * @param $params
     * @param $page
     * @return object
     */
    public function resolveRoute($name, $params, $page) :object
    {

        $this->route = [
            'name'   => $name,
            'params' => $params,
            'query' => $this->getQuery($name, $params, $page)
        ];


        return $this;
    }

    /**
     * Get query for the specified route
     *
     * @param $name
     * @param $params
     * @param $page
     * @return array
     */
    protected function getQuery($name, $params, $page)
    {
        if(empty($params['resourceId']) && $name != 'create')
            return [
                $params['resourceName'].'_page' => $page
            ];
        else
            return [];
    }

    /**
     * Used for restricting the display of global fields
     *
     * @return $this
     */
    public function withoutGlobalFields()
    {
        $this->withGlobalFields = false;

        return $this;
    }

    /**
     * Used to get additional information for vue component
     *
     * @return array
     */
    public function resolve() : array
    {
        return [
            'withGlobalFields' => $this->withGlobalFields,
            'buttonText' => $this->buttonText,
            'action' => $this->action,
            'isRoute' => $this->isRoute,
            'route' => $this->route,
            'isLink' => $this->isLink,
            'link' => $this->link
        ];
    }

    /**
     * @return string
     */
    public function getButtonText (): string
    {
        return $this->buttonText;
    }

    /**
     * Get the component name for the element.
     *
     * @return string
     */
    public function component()
    {
        return 'resource-custom-button';
    }

    /**
     * Serialize additional props
     *
     * @return array
     */
    public function jsonSerialize ()
    {
        return array_merge(parent::jsonSerialize(), $this->resolve());
    }

}
