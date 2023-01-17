<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 * Copyright (c) 2023 by multiple authors                      *
 * Politechnika Śląska | Silesian University of Technology     *
 *                                                             *
 * Nazwa pliku: PaginationHelper.php                           *
 * Projekt: restaurant-project-php-si                          *
 * Data utworzenia: 2023-01-05, 01:14:47                       *
 * Autor: Miłosz Gilga                                         *
 *                                                             *
 * Ostatnia modyfikacja: 2023-01-17 02:54:11                   *
 * Modyfikowany przez: Miłosz Gilga                            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace App\Services\Helpers;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class PaginationHelper
{
    private const AVAILABLE_PAGING = array(10, 15, 20, 50, 100);

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function get_pagination_nav($curr_page, $total_per_page, $total_pages, $total, $base_url)
    {
        if (strpos($base_url, '?')) $start_char = '&';
        else $start_char = '?';
        return array(
            'first_page' => array(
                'is_active' => $curr_page != 1 ? '' : 'disabled',
                'url' => $base_url . $start_char . 'page=1&total=' . $total_per_page,
            ),
            'prev_page' => array(
                'is_active' => $curr_page - 1 > 0 ? '' : 'disabled',
                'url' => $base_url . $start_char . 'page=' . ($curr_page - 1) . '&total=' . $total_per_page,  
            ),
            'next_page' => array(
                'is_active' => $curr_page < $total_pages ? '' : 'disabled',
                'url' => $base_url . $start_char . 'page=' . ($curr_page + 1) . '&total=' . $total_per_page, 
            ),
            'last_page' => array(
                'is_active' => $curr_page != $total_pages ? '' : 'disabled',
                'url' => $base_url . $start_char . 'page=' . $total_pages . '&total=' . $total_per_page,
            ),
            'records' => array(
                'first'=> ($curr_page - 1) * $total_per_page + 1,
                'last' => ($curr_page - 1) * $total_per_page + $total_per_page,
                'total' => $total,
            )
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function check_parameters($redirect_url)
    {
        if (!isset($_GET['page']) || !isset($_GET['total'])) return;
        $page = $_GET['page'];
        $total = $_GET['total'];
        if (!is_numeric($page) || !is_numeric($total))
        {
            header('Location:' . __URL_INIT_DIR__ . $redirect_url, true, 301);
            die;
        }
        $page = (int) $page;
        $total = (int) $total;
        if ($page < 1)
        {
            header('Location:' . __URL_INIT_DIR__ . $redirect_url, true, 301);
            die;
        }
        if (!in_array($total, self::AVAILABLE_PAGING, true))
        {
            header('Location:' . __URL_INIT_DIR__ . $redirect_url, true, 301);
            die;
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function check_if_page_is_greaten_than($redirect_url, $max)
    {
        if (!isset($_GET['page'])) return;
        $page = $_GET['page'];
        if (!is_numeric($page))
        {
            header('Location:' . __URL_INIT_DIR__ . $redirect_url, true, 301);
            die;
        }
        if ((int) $page > $max)
        {
            header('Location:' . __URL_INIT_DIR__ . $redirect_url, true, 301);
            die;
        }
    }
}
