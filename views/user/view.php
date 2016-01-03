<?php
use models\Update;
use components\UrlManager;
?>
<?php if (null === $model) : ?>
<div style="width: 100%;">
    <div style="margin: auto; width: 300px;
    padding-top: 30px;">
        <h1>NO USER FOUND</h1>
    </div>
</div>
<?php else : ?>
<?php
$this->title = $model['username'] . ' - ' . CW::$app->params['siteName'];
?>
<script>
$(function() {
    var updateLoader = App.update.Loader({
        updatesCont : document.getElementById('updates-cont'),
        loadingEle  : document.getElementById('loading'),
        noResultEle : document.getElementById('no-results-found'),
        url : <?= json_encode(UrlManager::to(['update/ajaxUserUpdates'])) ?>,
        ajaxData : {
            userId : <?= CW::$app->request->get('id') ?>,
            type : '<?= CW::$app->request->get('type') ?>',
        }
    });

    updateLoader.load();
});
</script>

<div id="user-info-area" style="background-color: #4B4F4A; color : white; width: 100%; padding: 20px 0px;">
    <div style="text-align: center;">
        <img style="border-radius: 200px;" src="<?= \models\User::getProfilePictureUrl($model['profile_img_id'], $model['id']) ?>" width="150" height="150">
    </div>
    <div style="text-align: center;font-weight:bold;">
        <?= htmlspecialchars($model['username']) ?>
    </div>
    <div style="text-align: center;max-width: 400px;margin: auto; margin-top: 20px;">
        <?= htmlspecialchars($model['description']) ?>
    </div>
</div>

<div style="text-align: center; background-color: white; line-height: 50px;">
    <ul class="user-act-opt-cont">
        <li class="user-act-opt <?= !in_array(CW::$app->request->get('type'), Update::getAllActivityTypesAsString()) ? 'user-act-selected' : '' ?>"><a class="user-act-link" href="<?= UrlManager::to(['user/view', 'id' => $model['id']]) ?>">All</a></li>
        <li class="user-act-opt <?= CW::$app->request->get('type') === Update::ACTIVITY_TYPE_POST_STR ? 'user-act-selected' : '' ?>"><a class="user-act-link" href="<?= UrlManager::to(['user/view', 'id' => $model['id'], 'type' => 'posted']) ?>">Posted</a></li>
        <li class="user-act-opt <?= CW::$app->request->get('type') === Update::ACTIVITY_TYPE_UPVOTE_STR ? 'user-act-selected' : '' ?>"><a class="user-act-link" href="<?= UrlManager::to(['user/view', 'id' => $model['id'], 'type' => 'upvoted']) ?>">Upvoted</a></li>
        <li class="user-act-opt <?= CW::$app->request->get('type') === Update::ACTIVITY_TYPE_COMMENT_STR ? 'user-act-selected' : '' ?>"><a class="user-act-link" href="<?= UrlManager::to(['user/view', 'id' => $model['id'], 'type' => 'commented']) ?>">Commented</a></li>
    </ul>
</div>

<div class="page-no-popular">
    <div style="width: 1200px;
        position: relative;
        margin: auto;">
        <div id="no-results-found" class="hidden" style="width: 200px; margin: auto;">
            <h3>No updates found.</h3>
        </div>
        <div id="updates-cont" class="msr-cont">
            
        </div>
        <div id="loading">
            <?= $this->render('../search/loading') ?>
        </div>
    </div>
</div>
<?php endif; ?>
