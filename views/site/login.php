<?php
use components\web\widgets\Form;
?>
<div class="start-page">
    <h2 class="header-title">Login</h2>
    <div class="start-page-cont">
    <?php
        $form = Form::widget([
            'options' => [
                'template' => '
                    <div class="inp-opt-cont">
                        <div>{label}</div>
                        <div class="int-cont">{input}</div>
                        <div class="int-cont-error">{error}</div>
                    </div>
                '
            ]
        ]);

        $form->input('email', $user, 'email', [
            'attrs' => [
                'class' => 'form-inp'
            ],
        ])
            ->input('password', $user, 'password', [
                'attrs' => [
                    'class' => 'form-inp'
                ],
            ]);
        ?>
        <div class="inp-opt-cont">
            <input type="checkbox" name="<?= $form->getClassName(false) ?>[remember_me]">
            <span style="vertical-align: middle;">remember me</span>
        </div>
        <div class="submit-cont">
            <input type="submit" value="login" class="submit-btn">
        </div>
        <?= $form->endForm() ?>
</div>
