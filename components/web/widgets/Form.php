<?php
namespace components\web\widgets;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class Form extends \classes\Object {

    private $id;
    private $method;

    private $jsValidators = [];
    private $jsOnChangeValidators = [];

    private $options;
    private $defaultTemplate = <<<TEMPLATE
        <div>{label}</div>
        <div style="margin-top: 4px;">
            {input}
        </div>
        <div style="margin-top: 4px;">
            {error}
        </div>
TEMPLATE;

    private function __construct() {
    }

    public static function widget($data = []) {
        $form = new static();
        
        $form->id = isset($data['id']) ? $data['id'] : '-';
        $form->method = isset($data['method']) ? $data['method'] : 'post';
        ob_start();

        echo sprintf('<form id="%s" action="%s" method="%s">',
            $form->id,
            isset($data['action']) ? $data['action'] : '',
            $form->method
        );

        $form->options = isset($data['options']) ? $data['options'] : [];

        return $form;
    }

    public function input(
        $type,
        $model,
        $objProp,
        $options = [],
        $list = []
    ) {
        $modelRules = $model->rules();

        $inpRules = isset($modelRules[$objProp]) ? $modelRules[$objProp] : [];

        if ('textarea' === $type) {
            $tag = 'textarea';
            $inp = sprintf('<textarea name="%s[%s]" value="%s" ',
                $model->getClassName(false),
                $objProp,
                $model->$objProp
            );
        } else if('checkboxList' === $type) {
            foreach ($list as $item) {
                
            }
        } else {
            $tag = 'input';
            $inp = sprintf('<input type="%s" name="%s[%s]" value="%s" ',
                $type,
                $model->getClassName(false),
                $objProp,
                'password' !== $type ? $model->$objProp : ''
            );
        }

        $attrs = isset($options['attrs']) ? $options['attrs'] : [];

        foreach ($attrs  as $prop => $value) {
            $inp .= sprintf('%s="%s" ', $prop, $value);
        }

        if (!isset($attrs['id'])) {
            $inputId = $model->getSimpleClassName() . '-' . \components\helpers\StringHelper::slugify($objProp);
        } else {
            $inputId = $attrs['id'];
        }

        $inp .= " id=\"$inputId\">";
        if ('textarea' === $tag) {
            $inp .= "</textarea>";
        }
        $template = isset($options['template']) ?
            $options['template'] :
            (isset($this->options['template']) ?
                $this->options['template'] : $this->defaultTemplate);

        $result = str_replace('{input}', $inp, $template);

        $labels = $model->getAttributeLabels();

        $result = str_replace('{label}', !empty($labels[$objProp]) ? $labels[$objProp] : '', $result);

        $errorSpan = sprintf('<span id="%d-error">%s</span>', $inputId, $model->getError($objProp));

        echo str_replace('{error}', $errorSpan, $result);

        $errMsg = isset($inpRules['message']) ? $inpRules['message'] : "$objProp is required.";
        $clientValidation = isset($inpRules['clientValidator']) ?
            sprintf("(%s)(%s)", $inpRules['clientValidator'](), '$("#' . $inputId . '").val()') :
            "(function() {return false;})()";

        $this->jsValidators[] = <<<JS
                if ($clientValidation) {
                    showError($("#$inputId-error"), '$errMsg');

                    return false;
                } else {
                    hideError($("#$inputId-error"));
                }
JS;

        $this->jsOnChangeValidators[] = <<<JS
            $("#$inputId").on('change', function() {
                if ($clientValidation) {
                    showError($("#$inputId-error"), '$errMsg');

                    return false;
                } else {
                    hideError($("#$inputId-error"));
                }
            });
JS;

        return $this;
    }

    private function textArea() {
        
    }

    public function submit($value) {
        echo sprintf('<input type="submit" value="%s">',
            $value
        );

        return $this;
    }

    public function endForm() {
        if ('post' === strtolower($this->method) && isset($_SESSION['_csrf'])) {
            echo sprintf('<input type="hidden" name="%s" value="%s">',
                '_csrf',
                \components\Security::hash($_SESSION['_csrf'])
            );
        }

        echo '</form>';

        $jsValidation = '';

        foreach ($this->jsValidators as $v) {
            $jsValidation .= "$v\n";
        }

        $jsFieldValidators = '';

        foreach ($this->jsOnChangeValidators as $v) {
            $jsFieldValidators .= "$v\n";
        }

        echo <<<JS
        <script>
        $(function() {
            function showError(jqEle, message) {
                jqEle.css({'display' : 'block'});
                jqEle.html(message);
            }
            function hideError(jqEle) {
                jqEle.css({'display' : 'none'});
            }
            $("#{$this->id}").on("submit", function() {
                $jsValidation
                return true;
            });
                    
            $jsFieldValidators
        });
        </script>
JS;

        return ob_get_clean();
    }

}
