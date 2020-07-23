<?php defined('SYSPATH') or die('No direct script access.');

use Elasticsearch\ClientBuilder;

class Controller_Search extends Controller_Base_preDispatch
{
    public function action_search()
    {
        $word = $this->request->param('word');

        $hosts = ['elasticsearch:9200'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();

        $params = [
            'index' => 'firstindex',
            'type' => 'article',
            'body' => [
                'query' => [
                    'match' => [
                        'text' => $word
                    ]
                ]
            ]
        ];

        $response = $client->search($params);

        $resultNames = array_map(function ($item) {
            return $item['_source'];
        }, $response['hits']['hits']);

        $this->template->content = View::factory('templates/pages/search', [
            'search' => $resultNames
        ]);
    }
}
