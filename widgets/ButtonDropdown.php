<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace humhub\modules\cfiles\widgets;

use yii\helpers\ArrayHelper;
use yii\bootstrap\Widget;
use yii\bootstrap\Html;
use yii\bootstrap\Button;
use yii\bootstrap\Dropdown;

/**
 * ButtonDropdown renders a group or split button dropdown bootstrap component.
 *
 * For example,
 *
 * ```php
 * // a button group using Dropdown widget
 * echo ButtonDropdown::widget([
 * 'label' => 'Action',
 * 'dropdown' => [
 * 'items' => [
 * ['label' => 'DropdownA', 'url' => '/'],
 * ['label' => 'DropdownB', 'url' => '#'],
 * ],
 * ],
 * ]);
 * ```
 *
 * @see http://getbootstrap.com/javascript/#buttons
 * @see http://getbootstrap.com/components/#btn-dropdowns
 * @author Antonio Ramirez <amigo.cobos@gmail.com>, edited by Sebastian Stumpf
 * @since 2.0
 */
class ButtonDropdown extends Widget
{

    /**
     *
     * @var array the HTML attributes for the container tag. The following special options are recognized:
     *     
     *      - tag: string, defaults to "div", the name of the container tag.
     *     
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     * @since 2.0.1
     */
    public $containerOptions = [];

    /**
     *
     * @var array the configuration array for [[Button]]
     */
    public $splitButton = [];

    /**
     *
     * @var array the configuration array for [[Dropdown]].
     */
    public $dropdown = [];

    /**
     *
     * @var array the configuration array for [[Button]]
     */
    public $button = [];

    /**
     * Renders the widget.
     */
    public function run()
    {
        // @todo use [[options]] instead of [[containerOptions]] and introduce [[buttonOptions]] before 2.1 release
        Html::addCssClass($this->containerOptions, [
            'widget' => 'btn-group'
        ]);
        $options = $this->containerOptions;
        $tag = ArrayHelper::remove($options, 'tag', 'div');

        $this->registerPlugin('button');
        return implode("\n", [
            Html::beginTag($tag, $options),
            $this->renderButton(),
            $this->renderDropdown(),
            Html::endTag($tag)
        ]);
    }

    /**
     * Generates the button dropdown.
     *
     * @return string the rendering result.
     */
    protected function renderButton()
    {
        $button = '';
        $splitButton = '';

        if (is_string($this->button)) {
            $button = $this->button;
        } else {
            $options = isset($this->button['options']) ? $this->button['options'] : [];
            if (empty($this->splitButton)) {
                $options['data-toggle'] = 'dropdown';
                Html::addCssClass($options, [
                    'toggle' => 'dropdown-toggle'
                ]);
            } else {
                Html::addCssClass($options, [
                    'widget' => 'btn',
                    'class' => 'split-button'
                ]);
            }
            $label = isset($this->button['label']) ? $this->button['label'] : null;
            $button = Button::widget([
                        'label' => (isset($this->button['encodeLabel']) && $this->button['encodeLabel'] ? Html::encode($label) : $label) . '  <span class="caret"></span>',
                        'encodeLabel' => false,
                        'options' => $options,
                        'view' => $this->getView()
            ]);
        }
        if (!empty($this->splitButton)) {
            if (is_string($this->splitButton)) {
                $splitButton = $this->splitButton;
            } else {
                $options = isset($this->splitButton['options']) ? $this->splitButton['options'] : [];
                Html::addCssClass($options, [
                    'widget' => 'btn'
                ]);
                $options['data-toggle'] = 'dropdown';
                Html::addCssClass($options, [
                    'toggle' => 'dropdown-toggle',
                    'class' => 'split-toggle'
                ]);
                $splitButton = Button::widget([
                            'label' => isset($this->splitButton['label']) ? $this->splitButton['label'] : '<span class="caret"></span>',
                            'encodeLabel' => false,
                            'options' => $options,
                            'view' => $this->getView()
                ]);
            }
        }
        return $splitButton . "\n" . $button;
    }

    /**
     * Generates the dropdown menu.
     *
     * @return string the rendering result.
     */
    protected function renderDropdown()
    {
        $config = $this->dropdown;
        $config['clientOptions'] = false;
        $config['view'] = $this->getView();

        return Dropdown::widget($config);
    }

}
