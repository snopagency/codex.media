<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Index extends Controller_Base_preDispatch
{

    const NEWS_LIMIT_PER_PAGE = 4;

    public function action_index()
    {
        $page_number = $this->request->param('page_number');
        if (!$page_number)
        {
            $page_number = 1;
        }

        $offset = ($page_number - 1) * self::NEWS_LIMIT_PER_PAGE;

        $pages = Model_Page::getPages(
                                    Model_Page::TYPE_SITE_NEWS, 
                                    self::NEWS_LIMIT_PER_PAGE + 1, 
                                    $offset);  

        $next_page = false;
        if (count($pages) > self::NEWS_LIMIT_PER_PAGE)
        {
            $next_page = true;
            unset($pages[self::NEWS_LIMIT_PER_PAGE]);

        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'))
        {
            $response = array();
            $response['success']    = 1;
            $response['next_page']  = $next_page;   
            $response['pages']      = Model_Page::modelToArray(null, $pages);

            $this->auto_render = false;
            $this->response->headers('Content-Type', 'application/json; charset=utf-8');
            $this->response->body( json_encode($response) );

        } else {

            $this->view['pages']        = $pages;
            $this->view['next_page']    = $next_page;
            $this->view['page_number']  = $page_number;

            $this->template->content = View::factory('templates/index', $this->view);

        }
    }

    public function action_contacts()
    {
        $this->template->title = 'Контакты';
        $this->template->content = View::factory('templates/contacts', $this->view);
    }
    
}