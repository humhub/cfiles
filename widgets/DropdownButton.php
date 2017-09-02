<?php

namespace humhub\modules\cfiles\widgets;

use yii\base\Widget;

class DropdownButton extends Widget
{

    public $buttons;
    public $icon;
    public $options;
    public $label;
    public $split = true;

    public function run()
    {
        $items = [];
        if (count($this->buttons) > 1) {
            foreach ($this->buttons as $button) {
                $items[] = '<li>' . str_replace($this->icon, '', $button) . '</li>';
            }
            if ($this->split) {
                array_shift($items);
            }
            return ButtonDropdown::widget([
                        'dropdown' => [
                            'items' => $items
                        ],
                        'splitButton' => $this->split ? [
                            'visible' => true,
                            'options' => $this->options
                        ] : null,
                        'button' => $this->split ? $this->buttons[0] : [
                            'encodeLabel' => false,
                            'label' => $this->icon . $this->label,
                            'options' => $this->options
                        ]
            ]);
        } elseif (count($this->buttons) > 0) {
            return $this->buttons[0];
        }
    }

}
