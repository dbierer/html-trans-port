<?php
namespace WP_CLI\Unlikely;
/**
 * Main wp-cli command class
 *
 * @author doug@unlikelysource.com
 * @date 2021-09-01
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

// https://codex.wordpress.org/Function_Reference
// https://developer.wordpress.org/reference/functions/get_categories/
// https://developer.wordpress.org/reference/functions/wp_insert_post/

use ArrayObject;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilterIterator;
use BadFunctionCallException;
use WP_CLI;
use WP_CLI_Command;

class HtmlTransPortCommand extends WP_CLI_Command
{
    public const ERROR_POS_ARGS = 'path to config file, source or destination directories missing or invalid: %s';
    public const ERROR_SINGLE   = 'single file not found';
    public const ERROR_SRC      = 'source directory path not found';
    public const ERROR_CONVERT  = 'conversion process error';
    public const ERROR_WP_POST  = 'unable to locate "wp-includes/post.php". Check your WordPress installation.';
    public const ERROR_POST_ERR = 'problem with "wp_insert_post()';
    public const SUCCESS_FILE   = 'Conversion successful! Out file name: %s';
    public const SUCCESS_PING   = 'We are alive and at line number: %d';
    public $container;
    /**
     * @param array $args       Indexed array of positional arguments.
     * @param array $assoc_args Associative array of associative arguments.
     */
    public function __invoke($args, $assoc_args)
    {
        if (!function_exists('wp_insert_post'))
            throw new BadFunctionCallException(static::ERROR_WP_POST);
        $container = $this->sanitizeParams($args, $assoc_args);
        // check params
        if ($container->status === ArgsContainer::STATUS_ERR) {
            WP_CLI::line($container->getErrorMessages());
            WP_CLI::halt(1);
        }
        // if single, convert single file
        if (!empty($container['single'])) {
            $extract = new Extract($container['single'], $container['config']);
            // if html-only, just return clean HTML
            if (!empty($container['no-import'])) {
                $err = [];
                $html = $extract->getHtml($err);
                if (!empty($html)) {
                    WP_CLI::line($html);
                } else {
                    WP_CLI::line(self::ERROR_CONVERT);
                    WP_CLI::error_multi_line($err);
                    WP_CLI::halt(1);
                }
            } else {
                $this->transPortSingle($extract, $container);
            }
        } else {
            // otherwise build a list of files
            $iter = $this->getDirIterator($container);
            // loop through list
            $iter->rewind();
            while ($iter->valid()) {
                $name = $iter->key();
                if (empty($extract)) {
                    $extract = new Extract($name, $container['config']);
                } else {
                    $extract->resetFile($name);
                }
                $this->transPortSingle($extract, $container);
                $iter->next();
            }
        }
    }
    /**
     * Transforms and imports (trans-ports) a single file
     *
     * @param Extract $extract
     * @param ArrayObject $container
     * @return string $xml_fn : full path to XML file | NULL if none written
     */
    public function transPortSingle(Extract $extract, ArrayObject $container)
    {
        $success = FALSE;
        // build target path and FN
        $fn = $extract->file_obj->getBasename($extract->file_obj->getExtension());
        $dest = $container['dest'] . DIRECTORY_SEPARATOR . $fn . '.json';
        $double = DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR;
        $dest = str_replace($double, DIRECTORY_SEPARATOR, $dest);
        $json = new BuildJSON($container['config'], $extract);
        $post = $build->buildJSON($dest);
        if (empty($post)) {
            WP_CLI::line(self::ERROR_CONVERT);
            WP_CLI::error_multi_line($extract->err);
            WP_CLI::halt(1);
        } else {
            $err = FALSE;
            try {
                $result = wp_insert_post($post, $err);
                if (is_wp_error($err)) {
                    WP_CLI::error_multi_line(var_export($err, TRUE));
                    error_log(__METHOD__ . ':' . var_export($err, TRUE));
                    WP_CLI::line(static::ERROR_WP_POST);
                    WP_CLI::halt(1);
                } elseif ($result === 0) {
                    WP_CLI::line(self::ERROR_CONVERT);
                    WP_CLI::halt(1);
                } else  {
                    $success = TRUE;
                    WP_CLI::line(sprintf(self::SUCCESS_FILE, $fn));
                }
            } catch (Throwable $t) {
                error_log(__METHOD__ . ':' . get_class($t) . ':' . $t->getMessage());
                WP_CLI::line(self::ERROR_CONVERT);
                WP_CLI::halt(1);
            }
        }
        return $success;
    }
    /**
     * Returns recursive directory iteration
     *
     * @param ArrayObject $container
     * @return iterable $dirIterator : filtered recursive directory iterator
     */
    public function getDirIterator(ArrayObject $container) : iterable
    {
        $src = $container['src'];   // path to start recursion
        $ext = $container['ext'];   // array of extensions to include
        $iter = new RecursiveDirectoryIterator($src);
        $iterPlus = new RecursiveIteratorIterator($iter);
        $filtIter = new class ($iterPlus, $ext) extends FilterIterator {
            public $ext = [];
            public function __construct($iter, $ext)
            {
                parent::__construct($iter);
                $this->ext = $ext;
            }
            public function accept()
            {
                $info = pathinfo($this->key());
                return (in_array($info['extension'], $this->ext));
            }
        };
        return $filtIter;
    }
    /**
     * Sanitizes incoming args
     * Error status is stored in $arg_container->error
     * Error messages are stored in $arg_container->error_msg
     *
     * @param array $args : positional params
     * @param array $assoc : names params
     * @return ArrayObject $arg_container | FALSE
     */
    public function sanitizeParams(array $args, array $assoc)
    {
        // santize $config param
        $container = new ArgsContainer();
        $src       = $args[2] ?? '';
        $dest_dir  = $args[1] ?? '';
        $config    = $args[0] ?? '';
        // sanitize $src param
        if (empty($src) || !file_exists($src) || !is_dir($src)) {
            error_log(__METHOD__ . ':' . __LINE__ . ':' . self::ERROR_SRC . ':' . $src);
            $container->addErrorMessage(sprintf(self::ERROR_POS_ARGS, $src));
        } else {
            $container->offsetSet('src', $src);
        }
        // sanitize dest_dir param
        if (empty($dest_dir) || !file_exists($dest_dir) || !is_dir($dest_dir)) {
            $container->addErrorMessage(sprintf(self::ERROR_POS_ARGS, $dest_dir));
        } else {
            $container->offsetSet('dest', $dest_dir);
        }
        // sanitize config param
        if (empty($config) || !file_exists($config)) {
            $container->addErrorMessage(sprintf(self::ERROR_POS_ARGS, $config));
        } else {
            $container->offsetSet('config', require $config);
        }
        if ($container->status === ArgsContainer::STATUS_ERR) return $container;
        // grab optional params
        $single = $assoc_args['single']  ?? '';
        $ext    = $assoc_args['ext']     ?? 'html';
        $only   = (!empty($assoc_args['html-only'])) ? TRUE : FALSE;
        // sanitize $next_id param
        $container->offsetSet('next-id', (int) $next_id);
        // sanitize $single param
        if (!empty($single)) {
            if ($single[0] !== DIRECTORY_SEPARATOR) {
                $fn = $src . DIRECTORY_SEPARATOR . $single;
                $double = DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR;
                $fn = str_replace($double, DIRECTORY_SEPARATOR, $fn);
                $fn = WP_CLI\Utils\normalize_path($fn);
                if (!file_exists($fn)) {
                    error_log(__METHOD__ . ':' . __LINE__ . ':' . self::ERROR_SINGLE . ':' . $fn);
                    $container->addErrorMessage(self::ERROR_SINGLE);
                    return $container;
                } else {
                    $container->offsetSet('single', $single);
                }
            }
        }
        // sanitize $ext
        if (strpos($ext, ',') !== FALSE) {
        $ext = explode(',', $ext);
        } else {
            $ext = [$ext];
        }
        $container->offsetSet('ext', $ext);
        // html-only flag
        $container->offsetSet('html-only', $only);
        return $container;
    }
}
