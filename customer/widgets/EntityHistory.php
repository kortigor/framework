<?php

declare(strict_types=1);

namespace customer\widgets;

use core\widgets\Widget;
use customer\entities\HistoryTrackedInterface;

/**
 * Show entity history
 */
class EntityHistory extends Widget
{
    /**
     * @var string
     */
    public string $templatesDir = '/backend/views/history/';

    /**
     * @var string Entity implements HistoryTrackedInterface
     */
    public HistoryTrackedInterface $entity;

    /**
     * @var bool Show full history with details of what data was changed.
     */
    public bool $isFull = false;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $template = $this->isFull ? 'entity-history' : 'entity-history-short';
        return $this->getView()->renderPart($this->templatesDir . $template, ['history' => $this->entity->history]);
    }
}
