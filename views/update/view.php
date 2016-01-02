<?php
use components\UrlManager;
?>
<?php if (null === $update) : ?>
<div style="width: 100%;">
    <div style="margin: auto; width: 400px;
    padding-top: 30px;">
        <h1>NO UPDATE FOUND</h1>
    </div>
</div>
<?php else : ?>

<script>
var commentCreator;

$(function() {
    var left = false, right = false;

    window.onkeydown = function(e) {
        var url = null;

        if (!left && 37 == e.keyCode) {
            if (<?= $prevUpdateId ? 'true' : 'false' ?>) {
                window.location = <?= $categoryName ? 
                    json_encode(UrlManager::to(['update/view', 'id' => $prevUpdateId, 'category' => $categoryName]))
                    : json_encode(UrlManager::to(['update/view', 'id' => $prevUpdateId])) ?>;
            }
            left = true;
        } else if (!right && 39 == e.keyCode) {
            if (<?= $nextUpdateId ? 'true' : 'false' ?>) {
                window.location = <?= $categoryName ? 
                    json_encode(UrlManager::to(['update/view', 'id' => $nextUpdateId, 'category' => $categoryName]))
                    : json_encode(UrlManager::to(['update/view', 'id' => $nextUpdateId])) ?>;
            }
            right = true;
        } else {
            return;
        }
    };

    var last = null;
    var hasLoadedBefore = false;
    var page = 0;
    var _firstToComment = <?= 0 == $update['comments'] ? 'true' : 'false' ?>;
    var _hasMore = true;

    var uc = document.getElementById('comments-cont');
    var moreComments = document.getElementById('load-more-comments');

    commentCreator = document.getElementById('create-comment');

    if (commentCreator) {
        commentCreator.onkeydown = function(e) {
            if (13 === e.keyCode) {
                e.stopPropagation();
                e.preventDefault();

                App.comment.createComment({
                    updateId : <?= CW::$app->request->get('id') ?>,
                    content : commentCreator.value
                }, uc, function () {
                    if (_firstToComment) {
                        _firstToComment = false;

                        var firstToComment = document.getElementById('first-to-comment');

                        if (firstToComment) {
                            firstToComment.setAttribute('class', 'hidden');
                        }
                    }

                    commentCreator.value = '';
                }, function() {
                    return !_hasMore;
                });
            }
        };
    }

    function load() {
        App.comment.load({
            updateId : <?= CW::$app->request->get('id') ?>,
            last : last,
            page : page
        }, uc, false, function(time, count, hasMore) {
            page++;

            _hasMore = hasMore;

            if (!hasMore) {
                $(moreComments).addClass('hidden');

                if (!hasLoadedBefore) {
                    var firstToComment = document.getElementById('first-to-comment');

                    if (firstToComment) {
                        firstToComment.setAttribute('class', '');
                    }
                }
            } else {
                $(moreComments).removeClass('hidden');
            }

            hasLoadedBefore = true;
        });
    }

    moreComments.onclick = function() {
        load();
    };

    load();

    var upvotesEle = document.getElementById('upvotes');
    var upvote = document.getElementById('upvote-btn');

    var voted = <?= $update['voted'] ? 'true' : 'false' ?>;
    var upvotes = <?= $update['upvotes'] ?>;

    App.button.vote1({
        ele : upvote,
        beforeVote : function(_data) {
            _data.url = voted ?
                <?= json_encode(UrlManager::to(['update/unvote'])) ?>
                : <?= json_encode(UrlManager::to(['update/upvote'])) ?>;
            _data.data = {
                id : <?= $update['id'] ?>
            };

            return true;
        },
        onVote : function(response) {
            if (response) {
                upvotes = voted ? (upvotes - 1) : (upvotes + 1);
                voted = !voted;

                upvote.setAttribute('style', voted ? 'margin-right: 7px; color: #09f;' : 'margin-right: 7px;');
                upvote.setAttribute('title', voted ? 'unvote' : 'upvote');
            }
        }
    });

    upvote.setAttribute('class', 'fa fa-thumbs-up update-btn');
    upvote.setAttribute('title', voted ? 'unvote' : 'upvote');
    upvote.setAttribute('style', voted ? 'margin-right: 7px; color: #09f;' : 'margin-right: 7px;');
});
</script>

<div style="width : 800px; margin : auto; position: relative; ">
    <h2 class='update-title'><?= $update['description'] ?>
    <?php foreach ($categories as $category) : ?>
        <a href="<?= \components\UrlManager::to(['site/index', 'category' => $category['name']]) ?>" style="font-weight: initial;
    font-size: initial;
    vertical-align: middle;
    background-color: #3DAD3D;
color: white;
text-decoration: none;
padding: 1px 5px;"><?= htmlspecialchars($category['name']) ?></a>
        <?php endforeach; ?>
    </h2>
    <div style="text-align: center;
        vertical-align: top;
        outline: 1px solid #ddd; background-color: black;">
        <?php if ($update['is_gif']) : ?>
        <video poster="/images/updates/<?= $update['id'] ?>/poster.jpeg" style="min-height:209.78260869565px;width: 500px; vertical-align: top;" width="500"
        loop muted autoplay="true"
        >
            <source src="/images/updates/<?= $update['id'] ?>/medium.mp4">
            <source src="/images/updates/<?= $update['id'] ?>/medium.webm">
        </video>
        <?php else : ?>
        <img class="image" src="<?= $update['imageUrl'] ?>">
        <?php endif; ?>
    </div>
    <?php if (!empty($update['tags'])) : ?>
    <div style="padding: 10px 5px;">
        <?php foreach ($update['tags'] as $tag) : ?>
        <a class="link" href="<?= \components\UrlManager::to(['site/search', 'term' => $tag['name']]) ?>">#<?= htmlspecialchars($tag['name']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div id='update-buttons'>
        <a id="upvotes" class="btn">points <?= $update['upvotes'] ?></a> - <a class="btn" href="#comments">comments <?= $update['comments'] ?></a>
        <?php if (CW::$app->user->isLogged()) : ?>
        <div style="margin-top: 10px;"><i id="upvote-btn"></i></div>
        <?php endif; ?>
    </div>
    <?php if (CW::$app->user->isLogged()) : ?>
    <div>
        <textarea id="create-comment" placeholder="Write a comment..."></textarea>
    </div>
    <?php endif; ?>
    <div id="comments" class="comments">
        <div id="comments-cont"></div>
        <div>
            <span id="load-more-comments" class="load-more-comments hidden">Show more comments</span>
        </div>
        <?php if (0 == $update['comments'] && CW::$app->user->isLogged()) : ?>
        <div id="first-to-comment" class="hidden" style="text-align: center;">
            <span style="font-weight: bold;">
                Be the first to comment!
            </span>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
