<?php
namespace humhub\modules\cfiles\widgets;

use yii\bootstrap\ButtonDropdown;

class DropdownButton extends \yii\base\Widget
{

    public $buttons;

    public $icon;
    
    public $options;
    
    public $label;

    public function run()
    {
        $items = [];
        if (count($this->buttons) > 1) {
            foreach ($this->buttons as $button) {
                $items[] = '<li>' . str_replace($this->icon, '', $button) . '</li>';
            }
            return ButtonDropdown::widget([
                'encodeLabel' => false,
                'label' => $this->icon . $this->label,
                'dropdown' => [
                    'items' => $items
                ],
                'options' => $this->options
            ]);
        } elseif (count($this->buttons) > 0) {
            return $this->buttons[0];
        }
    }
}

?>