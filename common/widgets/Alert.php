<?php

declare(strict_types=1);

namespace common\widgets;

use core\collections\FlashCollection;

/**
 * Alert widget renders a message from flash collection. All flash messages are displayed
 * in the sequence they were assigned using set(). You can set message as following:
 *
 * ```php
 * $collection->set('error', 'This is the message');
 * $collection->set('success', 'This is the message');
 * $collection->set('info', 'This is the message');
 * ```
 *
 * Multiple messages could be set as follows:
 *
 * ```php
 * $collection->set('error', ['Error 1', 'Error 2']);
 * ```
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @author Igor Kortava <kort.igor@gmail.com>
 */
class Alert extends \core\bootstrap4\Widget
{
    /**
     * @var array the alert types configuration for the flash messages.
     * This array is setup as $key => $value, where:
     * - key: the name of the collection flash variable
     * - value: the bootstrap alert type (i.e. danger, success, info, warning)
     */
    public array $alertTypes = [
        'error'   => 'alert-danger',
        'danger'  => 'alert-danger',
        'success' => 'alert-success',
        'info'    => 'alert-info',
        'warning' => 'alert-warning'
    ];

    /**
     * @var array the options for rendering the close button tag.
     * Array will be passed to [[\core\bootstrap4\Alert::closeButton]].
     */
    public array $closeButton = [];

    /**
     * @var FlashCollection Collection of flashes to display
     */
    public FlashCollection $collection;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if (!isset($this->collection)) {
            return;
        }

        $flashes = $this->collection->getAll();
        $appendClass = isset($this->options['class']) ? ' ' . $this->options['class'] : '';

        foreach ($flashes as $type => $flash) {
            if (!isset($this->alertTypes[$type])) {
                continue;
            }

            foreach ((array) $flash as $i => $message) {
                echo \core\bootstrap4\Alert::widget([
                    'body' => $message,
                    'closeButton' => $this->closeButton,
                    'options' => array_merge($this->options, [
                        'id' => $this->getId() . '-' . $type . '-' . $i,
                        'class' => $this->alertTypes[$type] . $appendClass,
                    ]),
                ]);
            }
        }
    }
}
