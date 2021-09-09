unlikely/html-trans-port
========================

Transforms HTML, saves in JSON format, imports as posts into WordPress

[![Build Status](https://travis-ci.org/unlikely/html-trans-port.svg?branch=main)](https://travis-ci.org/unlikely/html-trans-port)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

# HTML to JSON Conveter
WP-CLI command that converts standalone HTML file(s) to JSON import files.
In the configuration file you can specify delimiters that tell the plugin how to extract content from the HTML pages.
Using configuration you can extract content and strip out extraneous headers or footers.

## Using
The generic usage is as follows:
```
wp html-trans-port config dest [--src=STARTING_DIR] [--single=SINGLE_FILE] [--ext=HTML]
```

### Options summary

| Option   | Optional | Notes |
| :------- | :------- | :---- |
| config | N | Path to the configuration file that controls how the conversion takes place |
| dest   | N | Directory where JSON files will be stored for later import |
| --src  | Y | Use this option to specify where to start converting.  If specified, to not use the `--file` option |
| --file | Y | Use this option if you only want to convert a single file.  If specified, to not use the `--dir` option |
| --ext  | Y | Use this if the extension for files to be converted is not "HTML".  If you want to convert multiple extensions, separate them with a comma. |
| --no-import | Y | Creates JSON files from transformed HTML files, but does not import into WordPress. |

Example converting multiple extensions in /httpdocs directory, writing to /tmp:
```
wp html-trans-port -c /config/config.php -d /tmp  -s /httpdocs -x html,phtml
```


## Configuration file options
The config file is `src/config/config.php`.
Here is a summary of the primary configuration keys:

### Export
The `export::rss` key should only be updated when the JSON specification changes.
The `export::channel` key needs to be completed with the appropriate values
* Leave the `export::channel::pubDate` key as-is
* Only update the `export::channel::generator` key if a new version is available

## Importing JSON Files
To import, proceed as follows:
* Open a terminal window/command prompt
* Change to your main WordPress installation directory
* Perform the import, where `PATH` is the path to the JSON files you created using this plugin
```
wp import PATH
```

## Post Array Keys
Here's an article that explains the requirements for `wp_insert_post`:
[see: https://developer.wordpress.org/reference/functions/wp_insert_post/](see: https://developer.wordpress.org/reference/functions/wp_insert_post/)
| Key | Optional | Notes |
| :-- | :------- | :---- |
| ID  | Y | (int) The post ID. If equal to something other than 0, the post with that ID will be updated. Default 0. |
| post_author | N | (int) The ID of the user who added the post. Default is the current user ID. |
| post_date | Y | (string) The date of the post. Default is the current time. |
| post_date_gmt | Y | (string) The date of the post in the GMT timezone. Default is the value of $post_date. |
| post_content | N | (mixed) The post content. Default empty. |
| post_content_filtered | Y |  (string) The filtered post content. Default empty. |
| post_title | N |  (string) The post title. Default empty. |
| post_excerpt | Y |  (string) The post excerpt. Default empty. |
| post_status | Y | (string) The post status. Default 'draft'. |
| post_type | Y | (string) The post type. Default 'post'. |
| comment_status | Y | (string) Whether the post can accept comments. Accepts 'open' or 'closed'. Default is the value of 'default_comment_status' option. |
| ping_status | Y | (string) Whether the post can accept pings. Accepts 'open' or 'closed'. Default is the value of 'default_ping_status' option. |
| post_password | Y | (string) The password to access the post. Default empty. |
| post_name | N |  (string) The post name. Default is the sanitized post title when creating a new post. |
| to_ping | Y | (string) Space or carriage return-separated list of URLs to ping. Default empty. |
| pinged | Y | (string) Space or carriage return-separated list of URLs that have been pinged. Default empty. |
| post_modified | Y | (string) The date when the post was last modified. Default is the current time. |
| post_modified_gmt | Y | (string) The date when the post was last modified in the GMT timezone. Default is the current time. |
| post_parent | Y | (int) Set this for the post it belongs to, if any. Default 0. |
| menu_order | Y | (int) The order the post should be displayed in. Default 0. |
| post_mime_type | Y | (string) The mime type of the post. Default empty. |
| guid | Y | (string) Global Unique ID for referencing the post. Default empty. |
| import_id | Y | (int) The post ID to be used when inserting a new post. If specified, must not match any existing post ID. Default 0. |
| post_category | Y | (array) Array (int) of category IDs. Defaults to value of the 'default_category' option. |
| tags_input | Y | (array) Array of tag names, slugs, or IDs. Default empty. |
| tax_input | Y | (array) Array of taxonomy terms keyed by their taxonomy name. Default empty. |
| meta_input | Y | (array) Array of post meta values keyed by their post meta key. Default empty. |


## Installing

Installing this package requires WP-CLI v2.5 or greater. Update to the latest stable release with `wp cli update`.
You also need to install the `wordpress-importer` plugin or equivalent (needs to accept JSON files).

Once you've done so, you can install the latest stable version of this package with:

```bash
wp package install https://github.com/dbierer/unlikely-html-trans-port:@stable
```

To install the latest development version of this package, use the following command instead:

```bash
wp package install https://github.com/dbierer/unlikely-html-trans-port:dev-main
```

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/unlikely/html-trans-port/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/unlikely/html-trans-port/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/dbierer/unlikely-html-trans-port/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


