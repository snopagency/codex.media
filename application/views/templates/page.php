<div class="breadcrumb" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">

    <? if($navigation[0]->type != Model_Page::TYPE_USER_PAGE || $navigation[0]->is_menu_item == 1): ?>
        <a class="nav_chain" href="/" itemprop="url"><span itemprop="title">Главная</span></a>
    <? else: ?>
        <a class="nav_chain" href="/user/<?= $page->author->id ?>" itemprop="url"><span itemprop="title"><?= $page->author->name ?></span></a>
    <? endif ?>

    <? foreach ($navigation as $navig_page): ?> »
        <? if ($navig_page->id != $page->id): ?>
            <a href="/p/<?= $navig_page->id ?>/<?= $navig_page->uri ?>" itemprop="title" class="nav_chain">
                <?= $navig_page->title ?>
            </a>
        <? else: ?>
            <span itemprop="title" class="nav_chain">
                <?= $navig_page->title ?>
            </span>
        <? endif ?>
    <? endforeach ?>

    <? if( $can_modify_this_page ): ?>
        <div class="fl_r actions">
            <a class="textbutton" href="/p/<?= $page->id ?>/<?= $page->uri ?>/delete"><i class="icon-cancel"></i> Удалить</a>
            <a class="button iconic green" href="/p/<?= $page->id ?>/<?= $page->uri ?>/edit"><i class="icon-pencil"></i> Редактировать</a>
        </div>
    <? endif ?>

</div>

<h1 class="page_title">
	<?= $page->title ?>
</h1>
<article class="page_content">
	<?= $page->content ?>
</article>

<? if ($page->childrens): ?>
    <ul class="page_childrens clear">
        <? foreach ($page->childrens as $children): ?>
            <li><a href="/p/<?= $children->id ?>/<?= $children->uri ?>"><?= $children->title ?></a></li>
        <? endforeach ?>
    </ul>
<? endif; ?>
<? if ( $can_modify_this_page ): ?>
    <a class="button iconic green add_children_btn" href="/p/<?= $page->id ?>/<?= $page->uri ?>/add-page">
        <i class="icon-plus"></i>
        Вложенная страница
    </a>
<? endif; ?>
<? if (isset($files) && $files): ?>
    <div class="files">
    	<table class="page_files">
    		<? foreach ($files as $file): ?>
    			<tr>
    				<td class="ext"><span class="ext_tag"><?= $file['extension'] ?></span></td>
    				<td class="title"><?= $file['title'] ?></td>
    				<td>
    					<p class="size"><?= (int)$file['size'] < 1000 ? $file['size'] . PHP_EOL . 'КБ' : ceil($file['size'] / 1000) . PHP_EOL . 'МБ' ?></p>
    				</td>
    			</tr>
    		<? endforeach ?>
    	</table>
    </div>
<? endif; ?>
<div class="page_comments" id="page_comments">

    <h3>Комментарии</h3>
    <? if ($comments): ?>
        <? foreach ($comments as $comment): ?>
            <div class="comment_wrapper clear <?= $comment->parent_comment ? 'answer_wrapper' : '' ?>"
                 id="comment_<?= $comment->id ?>">
                <a href="/user/<?= $comment->author->id ?>">
                    <img class="comment_left" src="<?= $comment->author->photo ?>">
                </a>
                <div class="comment_right">

                    <time>
                        <?= date_format(date_create($comment->dt_create), 'd F Y') ?>
                    </time>

                    <a href="/user/<?= $comment->author->id ?>" class="author_name">
                        <?= $comment->author->name ?>
                    </a>

                    <? if ($comment->parent_comment): ?>
                        <span class="to_user">
                            <i class="icon-right-dir"></i>
                            <?= $comment->parent_comment['author']->name ?>
                        </span>
                    <? endif; ?>


                    <p><?= $comment->text ?></p>

                    <? if ($user->id): ?>
                        <span class="answer_button" id="answer_button_<?= $comment->id ?>"
                              data-comment-id="<?= $comment->id ?>"
                              data-root-id="<?= $comment->root_id ?>">
                            <i class="icon-reply"></i>
                            Ответить
                        </span>
                    <? endif; ?>

                    <? if ($user->id == $comment->author->id || $user->isAdmin): ?>
                        <a class="delete_button"
                           href="/p/<?= $page->id ?>/<?= $page->uri ?>/delete-comment/<?= $comment->id ?>">
                            Удалить
                        </a>
                    <? endif; ?>
                </div>

            </div>
        <? endforeach; ?>
    <? else: ?>
        <p class="dummy_text">Здесь пока нет комментариев.</p>
    <? endif; ?>
    <? if($user->id): ?>
        <form action="/p/<?= $page->id ?>/<?= $page->uri ?>/add-comment" id="comment_form" method="POST" class="comment_form mt20">
            <?= Form::hidden('csrf', Security::token()); ?>
            <textarea id="add_comment_textarea" name="add_comment_textarea" rows="5"></textarea>
            <input type="hidden" name="parent_id" value="0" id="parent_id"/>
            <input type="hidden" name="root_id" value="0" id="root_id"/>
            <input id="add_comment_button" disabled type="submit" value="Оставить комментарий" />
            <span id="add_answer_to" class="add_answer_to"></span>
            <span class="cancel_answer" id="cancel_answer" name="cancel_answer"><i class="icon-cancel"></i></span>
        </form>
    <? else: ?>
        <p class="dummy_text"><a href="/auth">Присоединяйтесь к сообществу</a>, чтобы оставлять комментарии.</p>
    <? endif; ?>
</div>

<script>
    $(function() {
        Comments.init();
    });
</script>