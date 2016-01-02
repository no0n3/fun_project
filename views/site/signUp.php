<?php
use components\web\widgets\Form;
?>
<div class="start-page">
    <?php if ($success) : ?>
    <div class="success">
        <i class="fa fa-check-circle"></i>
        <span class="success-msg">You have successfully signed up! <a class="success-link" href="<?= \components\UrlManager::to(['site/login']) ?>">Click here</a> login.</span>
    </div>
    <?php else : ?>
    <h2 class="header-title">Sign up</h2>
    <div class="start-page-cont">
    <?php
            $form = Form::widget([
                'options' => [
                    'template' => '
                        <div class="inp-opt-cont">
                            <div>{label}</div>
                            <div class="inp-cont">{input}</div>
                            <div class="inp-cont-error">{error}</div>
                        </div>
                    '
                ]
            ]);

             $form->input('email', $model, 'email', [
                'attrs' => [
                    'class' => 'form-inp',
                    'autocomplete' => 'off'
                ],
            ])
                ->input('text', $model, 'username', [
                'attrs' => [
                    'class' => 'form-inp',
                    'autocomplete' => 'off'
                ],
            ])
                ->input('password', $model, 'password', [
                    'attrs' => [
                        'class' => 'form-inp'
                    ],
                ]);
            ?>
            <div class="submit-cont">
                <input type="submit" value="sign up" class="submit-btn">
            </div>
            <?= $form->endForm() ?>
    </div>
    <?php endif; ?>
</div>
