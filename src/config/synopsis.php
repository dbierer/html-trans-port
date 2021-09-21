<?php
return [
    'shortdesc' => 'Transforms standalone HTML files, saves as JSON file, imports them as WordPress posts',
    'synopsis' => [
        [
            'type'        => 'positional',
            'name'        => 'src',
            'description' => 'Starting directory path indicates where to start tranforming',
            'optional'    => true,
            'default'     => 'current directory',
            //'options'     => [ 'success', 'error' ],
        ],
        [
            'type'        => 'positional',
            'name'        => 'dest',
            'description' => 'Provide a directory path for JSON trans-port files',
            'optional'    => false,
            'repeating'   => false,
        ],
        [
            'type'        => 'positional',
            'name'        => 'config',
            'description' => 'Path to the configuration file',
            'optional'    => false,
            'repeating'   => false,
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
    'longdesc' => 'Available transformations include extracting HTML content between specified delimiters, clean and repair HTML using the Tidy extension, remove specified attributes, remove specified blocks, search and replace and converting TABLE tags to DIV class="row" and DIV class="col"'
                  . '## EXAMPLES' . "\n\Transforms and imports all htm,html and phtml files from 'httpdocs' and writes JSON contents to '/tmp' \n" . 'wp html-trans-port ./httpdocs /tmp ./config/config.php --ext=htm,html,phtml',
];
