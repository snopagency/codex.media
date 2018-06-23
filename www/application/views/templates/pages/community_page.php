<?= View::factory('templates/components/about', [
    'title'=>'About',
    'description' => $page->description,
    'organization_name' => $page->title
])->render(); ?>

<div class="island tabs island--margined">
    <a class="tabs__tab tabs__tab--current" href="<?= $page->url ?>">
        General
    </a>
</div>

<?= View::factory('templates/pages/list', [
    'pages' => $page->children,
    'emptyListMessage' => 'Тут появятся статьи и заметки',
    'active_tab' => ''
]); ?>