<?php
namespace Sprout\Widgets;

use Kohana;

use Sprout\Helpers\View;
use Sprout\Widgets\Widget;


class PageColumnsWidget extends Widget
{
    protected $friendly_name = "Page columns";
    protected $friendly_desc = 'Setup page columns';
    public $classname = 'PageColumnsWidget';


    public static $cols = [
        '1 column - 100%',
        '2 columns - 50:50',
        '2 columns - 60:40',
        '2 columns - 40:60',
        '3 columns - 33:33:33',
    ];


    /**
     * Renders front-end view of this widget
     *
     * @param int $orientation The orientation of the widget.
     * @return string HTML
     */
    public function render($orientation)
    {
        return '';
    }


    /**
     * Render admin settings form for this widget
     *
     * @return string HTML
     */
    public function getSettingsForm()
    {
        $styles = Kohana::config('sprout.widget_columns_classes');
        if (empty($styles)) $styles = ['' => 'None'];

        $view = new View('sprout/admin/page_columns_widget');
        $view->columns = array_combine(PageColumnsWidget::$cols, PageColumnsWidget::$cols);
        $view->styles = $styles;

        return $view->render();
    }
}
