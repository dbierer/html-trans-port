<?php
// provides configuration for WP import/export tool

use WP_CLI\Unlikely\Import\Extract;
use WP_CLI\Unlikely\Import\Transform\{
    CleanAttributes,
    Clean,
    RemoveAttributes,
    RemoveBlock,
    TableToDiv,
    Replace
};
$config = [
    // callbacks used to produce post values
    'post' => [
        'ID' => 0,
        'post_author' => 1,
        'post_date' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getCreateDate'
            ]
        ],
        'post_date_gmt' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getCreateDate',
                'args' => 'UTC',
            ]
        ],
        'post_content' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getHtml',
                'args' => 'base64_encode',
            ]
        ],
        'post_content_filtered' => [],
        'post_title' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getTitle'
            ],
        ],
        'post_excerpt' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getExcerpt'
            ]
        ],
        'post_status' => 'draft',
        'post_type' => 'post',
        'comment_status' => NULL,
        'ping_status' => NULL,
        'post_password' => NULL,
        'post_name' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getWpFilename',
            ]
        ],
        'to_ping' => NULL,
        'pinged' => NULL,
        'post_modified' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getModifyDate'
            ]
        ],
        'post_modified_gmt' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getModifyDate',
                'args' => 'UTC'
            ]
        ],
        'post_parent' => 0,
        'menu_order' => 0,
        'post_mime_type' => NULL,
        'guid' => NULL,
        'import_id' => 0,
        'post_category' => [
            'callback' => [
                'class' => Extract::class,
                'method' => 'getLastDir'
            ]
        ],
        'tags_input' => [],
        'tax_input' => [],
        'meta_input' => [],
    ],
    //**********************************************
    // main extraction
    //**********************************************
    Extract::class => [
        'attrib_list'  => Extract::DEFAULT_ATTR_LIST,                               // list of attributes to strip
        'delim_start'  => '<!--#include virtual="/sidemenu_include.html" -->',     // marks beginning of contents to extract
        'delim_stop'   => '<!--#include virtual="/footer_include.html" -->',       // marks end of contents to extract
        'title_regex'  => Extract::TITLE_REGEX,         // regex to extract title
        'excerpt_tags' => Extract::EXCERPT_TAGS,        // tags(s) to search for to locate extract
        'start_id'     => 101,                          // starting post ID number
        'transform' => [
            'clean' => [
                'callback' => new Clean(),
                'params' => ['bodyOnly' => TRUE]
            ],
            'remove_block' => [
                'callback' => new RemoveBlock(),
                'params' => ['start' => '<tr height="20">','stop' => '</tr>','items' => ['bkgnd_tandk.gif','trans_spacer50.gif','bkgnd_tanlt.gif']],
            ],
            'table_to_row_col_div' => [
                'callback' => new TableToDiv(),
                'params' => ['td' => 'col', 'th' => 'col bold', 'row' => 'row', 'width' => 12],
            ],
            'attribs_remove' => [
                'callback' => new RemoveAttributes(),
                'params' => ['attributes' => Extract::DEFAULT_ATTR_LIST]
            ],
            'replace_dentalwellness' => [
                'callback' => new Replace(),
                'params' => ['search' => 'https://www.dentalwellness4u.com', 'replace' => '', 'case-sensitive' => FALSE]
            ],
        ],
    ],
    //**********************************************
    // other callback classes can be registered here
    //**********************************************
];

return $config;
