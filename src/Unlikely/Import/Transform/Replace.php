<?php
namespace WP_CLI\Unlikely\Import\Transform;

/*
 * Unlikely\Import\Transform\Replace
 *
 * Uses Tidy extension to clean up HTML fragment
 * Removes extra header and footer added by Tidy
 *
 * @author doug@unlikelysource.com
 * @date 2021-09-07
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
class Replace implements TransformInterface
{
    /**
     * Performs search and replace
     *
     * @param string $html  : HTML string to be cleaned
     * @param array $params : ['search' => search for this, 'replace' => replace with this, 'case-sensitive' => bool]
     * @return string $html : transformed HTML
     */
    public function __invoke(string $html, array $params = []) : string
    {
        $search = $params['search'] ?? '';
        $replace = $params['replace'] ?? '';
        $case = (bool) ($params['case-sensitive'] ?? FALSE);
        if (!empty($search)) {
            $html = ($case)
                  ? str_replace($search, $replace, $html)
                  : str_ireplace($search, $replace, $html);
        }
        return $html;
    }
}
