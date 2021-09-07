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
use DateTime;
use DateTimeZone;
use SplFileObject;
use ArrayIterator;

class BuildJSON extends BuildBase
{
    public const ERR_ITEM_KEY = 'ERROR: problem with "item" configuration.  All values must be arrays. "configuration" treated separately.';
    public $post = [];            // import template config
    public $json = NULL;          // JSON string
    /**
     * Initializes delimiters and creates transform callback array
     *
     * @param array $config : ['export' => ['rss' => [attribs], 'channel' => [WXR nodes]], 'item' => [config for building "item" node]]
     * @param Extract $extract : new Extract instance
     */
    public function __construct(array $config, Extract $extract = NULL)
    {
        parent::__construct($config, $extract);
        $this->post  = $config['post'] ?? [];
    }
    /**
     * Builds import JSON
     *
     * @param string $fn   : if present, writes JSON to this filename
     * @param array|null $item : override configuration for building "post" node
     * @param bool $test   : set to TRUE for testing
     * @return array $post
     */
    public function buildJSON(string $fn = '', ?array $item = NULL, bool $test = FALSE) : array
    {
        $post = [];
        $item = $item ?? $this->post;
        foreach ($item as $key => $value) {
            if (is_array($value)) {
                if (isset($value['callback'])) {
                    $post[$key] = $this->doCallback($value['callback']);
                } else {
                    $post[$key] = $value;
                }
            } else {
                $post[$key] = $value;
            }
        }
        $this->json = json_encode($post, JSON_PRETTY_PRINT);
        if (!empty($fn)) file_put_contents($this->json);
        return $post;
    }
}
