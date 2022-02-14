<?php

namespace App\Symfony;

use App\Entity\HasMetaTimestampsInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class MetaTimestampsPrePersistEventListener
{
    public function prePersist(LifecycleEventArgs $event): void
    {
        $entity = $event->getObject();

        if ($entity instanceof HasMetaTimestampsInterface) {
            $entity->setCreatedAt();
            $entity->setUpdatedAt();
        }
    }
}
