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

use ArrayObject;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilterIterator;
use WP_CLI;
use WP_CLI_Command;

class HtmlTransPortCommand extends WP_CLI_Command
{
    public const ERROR_POS_ARGS = 'path to config file and/or destination directory to write WXR files missing or invalid';
    public const ERROR_SINGLE   = 'single file not found';
    public const ERROR_SRC      = 'source directory path not found';
    public const ERROR_CONVERT  = 'conversion process error';
    public const SUCCESS_FILE   = 'Conversion successful! Out file name: %s';
    public const SYNOPSIS = [
        'shortdesc' => 'Transforms standalone HTML files and imports them as WordPress posts',
        'synopsis' => [
            [
                'type'        => 'positional',
                'name'        => 'config',
                'description' => 'Path to the configuration file',
                'optional'    => false,
                'repeating'   => false,
            ],
            [
                'type'        => 'positional',
                'name'        => 'dest',
                'description' => 'Provide a directory path for JSON trans-port files',
                'optional'    => false,
                'repeating'   => false,
            ],
            [
                'type'        => 'assoc',
                'name'        => 'src',
                'description' => 'Starting directory path indicates where to start tranforming',
                'optional'    => true,
                'default'     => 'current directory',
                //'options'     => [ 'success', 'error' ],
            ],
            [
                'type'        => 'assoc',
                'name'        => 'single',
                'description' => 'Single file to trans-port. If full path to file is not provided, prepends the value of "src" to "single".  See also: "html-only"',
                'optional'    => true,
                'default'     => 'NULL',
                //'options'     => [ 'success', 'error' ],
            ],
            [
                'type'        => 'assoc',
                'name'        => 'ext',
                'description' => 'Extension(s) other than "html" to convert.  If multiple extension, separate extensions with comma(s)',
                'optional'    => true,
                'default'     => 'html',
                //'options'     => [ 'success', 'error' ],
            ],
            [
                'type'        => 'assoc',
                'name'        => 'no-import',
                'description' => 'If set to "1", this flag causes no JSON file to be created: only the cleaned and sanitized extracted HTML; only works with the "single" option',
                'optional'    => true,
                'default'     => 'FALSE',
                //'options'     => [ 'success', 'error' ],
            ],
        ],
        'when' => 'after_wp_load',
        'longdesc' =>   '## EXAMPLES' . "\n\n" . 'wp html-trans-port /config/config.php --src=/httpdocs --ext=htm,html,phtml',
    ];
    public $container;
    /**
     * @param array $args       Indexed array of positional arguments.
     * @param array $assoc_args Associative array of associative arguments.
     */
    public function __invoke($args, $assoc_args)
    {
        $container = $this->sanitizeParams($args, $assoc_args);
        if ($container->status === ArgsContainer::STATUS_ERR) {
            WP_CLI::error_multi_line($obj->getErrorMessages(), TRUE);
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
        } else {
            $err = FALSE;
            try {
                $result = wp_insert_post($post, $err);
                if (is_wp_error($err)) {
                    WP_CLI::error_multi_line($err->errors);
                    error_log(__METHOD__ . ':' . var_export($err->errors, TRUE));
                } elseif ($result === 0) {
                    WP_CLI::line(self::ERROR_CONVERT);
                } else  {
                    $success = TRUE;
                    WP_CLI::line(sprintf(self::SUCCESS_FILE, $fn));
                }
            } catch (Throwable $t) {
                error_log(__METHOD__ . ':' . get_class($t) . ':' . $t->getMessage());
                WP_CLI::line(self::ERROR_CONVERT);
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
        $config = WP_CLI::line($args[0]) ?? '';
        $dest_dir = WP_CLI::line($args[1]) ?? '';
        if (empty($config) || empty($dest_dir) || !file_exists($config) || !file_exists($dest_dir)) {
            $container->addErrorMessage(self::ERROR_POS_ARGS);
            return $container;
        } else {
            $container->offsetSet('config', require $config);
            $container->offsetSet('dest', $dest_dir);
        }
        // grab optional params
        $next_id= WP_CLI::line($assoc_args['next-id']) ?? 1;
        $src    = WP_CLI::line($assoc_args['src']) ?? \WP_CLI\Utils\get_home_dir();
        $single = WP_CLI::line($assoc_args['single']) ?? '';
        $ext    = WP_CLI::line($assoc_args['ext']) ?? 'html';
        $only   = (!empty(WP_CLI::line($assoc_args['html-only']))) ? TRUE : FALSE;
        // sanitize $next_id param
        $container->offsetSet('next-id', (int) $next_id);
        // sanitize $src param
        if (!file_exists($src)) {
            error_log(__METHOD__ . ':' . __LINE__ . ':' . self::ERROR_SRC . ':' . $src);
            $container->addErrorMessage(self::ERROR_SRC);
            return $container;
        } else {
            $container->offsetSet('src', $src);
        }
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
