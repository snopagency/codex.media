<?php defined('SYSPATH') or die('No direct script access.');

use Elasticsearch\ClientBuilder;
use \EditorJS\EditorJS;
/**
 * Help task to display general instructons and list all tasks
 *
 * @package    Kohana
 * @category   Helpers
 * @author     Kohana Team
 * @copyright  (c) 2009-2011 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Task_Elastic extends Minion_Task
{
    private $client;

    protected function __construct()
    {
        parent::__construct();
        $this->client = ClientBuilder::create()->setHosts(
            ['elasticsearch:9200']
        )->build();
    }

    protected function _execute(array $params)
    {
        $this->cleanUp();
        $this->client = ClientBuilder::create()->setHosts(
            ['elasticsearch:9200']
        )->build();

        $pages = Dao_Pages::select()->execute();

        foreach ($pages as $page) {
            $this->client->index([
                'index' => 'pages',
                'type' => 'page',
                'body' => $this->transformPageData($page),
            ]);
        }

        /**
         * To check result open http://localhost:9200/pages/_search?size=1000 in browser
         */
    }

    private function cleanUp() {
        $this->client->deleteByQuery([
            'index' => 'pages',
            'type' => 'page',
            "body" => [
                "query" => [
                    "match_all" => (object)[]
                ]
            ]
        ]);
    }

    private function transformPageData($page_data)
    {
        $editor = new EditorJS($page_data['content'], $this->getEditorConfig());
        $content = $editor->getBlocks();

        $text = [];
        foreach ($content as $content_block) {
            if ($content_block['type'] == 'paragraph') {
                array_push($text, $content_block['data']['text']);
            }
        }

        return [
            'title' => $page_data['title'],
            'text' => empty($text) ? "" : implode("\n", $text)
        ];
    }

    private function getEditorConfig()
    {
        try {
            return file_get_contents(APPPATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'editorjs-config.json');
        } catch (Exception $e) {
            throw new Exceptions_ConfigMissedException("EditorJS config not found");
        }
    }
}
