<?php

namespace common\listeners;

use common\models\EntityHistory;
use common\events\EntityUpdated;
use common\events\EntityCreated;
use common\events\EntityBlocked;
use common\events\EntityUnBlocked;
use common\events\EntityUpdateEventInterface;

/**
 * Trait implements listener functionality to write entities changes history.
 */
trait AggregateListenerTrait
{
    public function onAnyEntityUpdate(EntityUpdateEventInterface $event): void
    {
        $history = EntityHistory::buildFromEntityEvent($event);
        $this->writeHistory($history);
    }

    public function onEntityUpdate(EntityUpdated $event): void
    {
        $history = EntityHistory::buildFromEntityEvent($event);
        $this->writeHistory($history);
        $event->stopPropagation();
    }

    public function onEntityCreate(EntityCreated $event): void
    {
        $history = EntityHistory::buildFromEntityEvent($event);
        $history->copy_before = $event->getModel()->getAttributes();
        $this->writeHistory($history);
        $event->stopPropagation();
    }

    public function onBlock(EntityBlocked $event): void
    {
        $history = EntityHistory::buildFromEntityEvent($event);
        $this->writeHistory($history);
        $event->stopPropagation();
    }

    public function onUnBlock(EntityUnBlocked $event): void
    {
        $history = EntityHistory::buildFromEntityEvent($event);
        $this->writeHistory($history);
        $event->stopPropagation();
    }

    private function writeHistory(EntityHistory $history): bool
    {
        if ($history->validate()) {
            return $history->save();
        }

        return false;
    }
}