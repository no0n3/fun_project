<h2> Change password </h2>
<?php
use components\web\widgets\Form;

$model = new \models\forms\ChangePasswordForm();
?>
<div class="start-page-cont" style="padding: 15px;">
    <?php
        $form = Form::widget([
            'options' => [
                'template' => '
                    <div style="padding-top: 10px; background-color: ;">
                        <div>{label}</div>
                        <div style="margin-top: 5px;">{input}</div>
                        <div style="margin-top: 5px; color: red;">{error}</div>
                    </div>
                '
            ]
        ]);

        echo $form->input('password', $model, 'oldPassword', [
            'attrs' => [
                'class' => 'form-inp',
                'style' => 'padding-top: 10px;'
            ],
        ])
            ->input('password', $model, 'newPassword', [
                'attrs' => [
                    'class' => 'form-inp'
                ],
            ])
            ->input('password', $model, 'confirmPassword', [
                'attrs' => [
                    'class' => 'form-inp'
                ],
            ]);
?>
    <div style="padding-top: 10px;">
        <input type="submit" value="change password" class="submit-btn">
    </div>
<?= $form->endForm() ?>
</div>
