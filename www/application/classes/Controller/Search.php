<?php defined('SYSPATH') or die('No direct script access.');

use Elasticsearch\ClientBuilder;

class Controller_Search extends Controller_Base_preDispatch
{
    public function action_search()
    {
        $this->auto_render = false;

        $word = Arr::get($_POST, 'word');

        $hosts = ['elasticsearch:9200'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();

        $params = [
            'index' => 'firstindex',
            'type' => 'user',
            'body' => [
                'query' => [
                    'match' => [
                        'text' => $word
                    ]
                ]
            ]
        ];

        $response = $client->search($params);
        $feed_key = Model_Feed_Pages::ALL;

        $result = [
            'search_result' => array_map(function ($item) {
                return $item['_source'];
            }, $response['hits']['hits'])
        ];

        $feed = new Model_Feed_Pages($feed_key);

        $pages = $feed->get(5);

        $result['searchResults'] = View::factory('templates/pages/list', array('pages' => $pages, 'active_tab' => $feed_key))->render();

        $this->response->headers('Content-Type', 'application/json; charset=utf-8');
        $this->response->body(@json_encode($result));
    }
}
