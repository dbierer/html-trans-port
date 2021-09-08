<?php
namespace WP_CLI\Unlikely;
/**
 * Program to invoke main command class
 *
 * @author doug@unlikelysource.com
 * @date 2021-09-06
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
require __DIR__ . '/class_loader.php';
use WP_CLI;
use WP_CLI\Unlikely\HtmlTransPortCommand;
if ( ! class_exists( 'WP_CLI' ) ) {
    echo "\nUnable to locate WP_CLI\n";
    return;
}
WP_CLI::add_command(
    'html-trans-port',
    HtmlTransPortCommand::class,
    HtmlTransPortCommand::SYNOPSIS
);
