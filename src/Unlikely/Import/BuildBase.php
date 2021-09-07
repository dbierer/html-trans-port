<?php
namespace WP_CLI\Unlikely\Import;
// see: https://developer.wordpress.org/reference/functions/wp_insert_post/

/*
 * Unlikely\Import\BuildJSON
 *
 * Produces JSON file
 *
 * @author doug@unlikelysource.com
 * @date 2021-08-21
 * Copyright 2021 unlikelysource.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 */

use Exception;
use BadMethodCallException;
use InvalidArgumentException;
use UnexpectedValueException;
use ArrayIterator;

class BuildBase
{
    public const ERR_CALLBACK = 'ERROR: unable to process callback: missing configuration?';
    public const ERR_CALLBACK_CLASS = 'ERROR: unable to process callback: missing configuration for %s class?';
    public const ERR_CALLBACK_METHOD = 'ERROR: unable to process callback method: missing configuration for %s class?';
    public const ERR_CALLBACK_INVALID = 'ERROR: callback must implement Unlikely\Import\BuildInterface';
    public $err = [];
    public $config = [];
    public $extract = NULL;         // instance of Extract class
    public $callbackManager = NULL; // stores additional callbacks (for future expansion)
    /**
     * Initializes delimiters and creates transform callback array
     *
     * @param array $config : ['export' => ['rss' => [attribs], 'channel' => [WXR nodes]], 'item' => [config for building "item" node]]
     * @param Extract $extract : new Extract instance
     */
    public function __construct(array $config, Extract $extract = NULL)
    {
        // bail out if unable to open $fn
        $this->err = [];
        $this->config  = $config;
        $this->callbackManager = new ArrayIterator();
        if (!empty($extract)) $this->setExtract($extract);
    }
    /**
     * Sets new Extract instance
     *
     * @param Extract $extract : new Extract instance
     * @return void
     */
    public function setExtract(Extract $extract)
    {
        $this->extract = $extract;
        $this->addCallback($extract);
    }
    /**
     * Retrieves class instance from callbackManager
     *
     * @param string $name : name of class to retrieve
     * @return object|NULL $obj
     */
    public function getCallback(string $name)
    {
        $this->checkCallbackManager();
        return ($this->callbackManager->offsetExists($name))
                ? $this->callbackManager->offsetGet($name)
                : NULL;
    }
    /**
     * Adds class instance to callbackManager
     *
     * @return void
     */
    public function addCallback(object $obj) : void
    {
        $this->checkCallbackManager();
        if (!$obj instanceof BuildInterface)
            throw new InvalidArgumentException(self::ERR_CALLBACK_INVALID);
        $key = str_replace("\0", '', get_class($obj));
        $this->callbackManager->offsetSet($key, $obj);
    }
    /**
     * Creates new callbackManager instance
     *
     * @return void
     */
    public function checkCallbackManager() : void
    {
        if (empty($this->callbackManager))
            $this->callbackManager = new ArrayIterator();
    }
    /**
     * Runs callbacks
     *
     * @param array $params  : ['class' => class name of callback, 'method' => method name, 'args' => optional arguments]
     *                         or ['callable' => callable $callback, 'args' => optional arguments]
     * @param string $method : method name of callback
     * @param mixed  $args   : arguments to provide to callback
     * @return mixed $result : result of callback | NULL if callback not found
     */
    public function doCallback(array $params)
    {
        $result = NULL;
        if (isset($params['callable'])) {
            $args = $params['args'] ?? [];
            $result = call_user_func($params['callable'], $args);
        } elseif (isset($params['class'])) {
            $result = $this->useCallbackManager($params);
        } else {
            error_log(__METHOD__ . ':' . static::ERR_CALLBACK);
            $result = NULL;
        }
        return $result;
    }
    /**
     * Provides for additional callbacks via $callbackManager
     *
     * All callbacks must accept an instance of this class as the first argument
     * in order to gain access to the original HTML file being imported
     *
     * @param string $method : name of the unique method name references by the CallbackManager
     * @param array $params  : array of params passed to the callback
     * @return mixed $unknown : return value from callback
     */
    public function useCallbackManager(array $params)
    {
        $result = NULL;
        $class  = $params['class']  ?? 'Unknown';
        $method = $params['method'] ??  'Unknown';
        $args   = $params['args']   ?? NULL;
        // scan to see if $class already exists
        $obj = $this->getCallback($class);
        if (!empty($obj) && is_object($obj)) {
            if (!method_exists($obj, $method))
                throw new BadMethodCallException(sprintf(static::ERR_CALLBACK_METHOD, $class));
            $obj->setBuildInstance($this);
            return $obj->$method($args) ?? NULL;
        }
        // if we get to this point, the callback class is not registered
        try {
            // pull config for this callback class
            $config = $this->config[$class] ?? [];
            if (empty($config))
                throw new Exception(sprintf(static::ERR_CALLBACK_CLASS, $class));
            // if we have config create the instance, store it and use it
            $callback = new $class(...$config);
            $this->addCallback($callback);
            // call method and pass $args and instance of this class
            $result = $callback->$method($args, $this);
        } catch (Throwable $t) {
            error_log(__METHOD__ . ':' . $t->getMessage());
        }
        return $result;
    }
}
