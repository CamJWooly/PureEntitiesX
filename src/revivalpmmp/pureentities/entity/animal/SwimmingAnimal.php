<?php

/*  PureEntitiesX: Mob AI Plugin for PMMP
    Copyright (C) 2017 RevivalPMMP

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>. */

namespace revivalpmmp\pureentities\entity\animal;

use revivalpmmp\pureentities\entity\SwimmingEntity;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class SwimmingAnimal extends SwimmingEntity implements Animal {

    public function getSpeed(): float {
        return 1.0;
    }

    public function initEntity() {
        parent::initEntity();

        if ($this->getDataFlag(self::DATA_FLAG_BABY, 0) === null) {
            $this->setDataFlag(self::DATA_FLAG_BABY, self::DATA_TYPE_BYTE, 0);
        }
    }

    public function isBaby(): bool {
        return $this->getDataFlag(self::DATA_FLAG_BABY, 0);
    }

    public function entityBaseTick($tickDiff = 1, $EnchantL = 0) {
        Timings::$timerEntityBaseTick->startTiming();

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if (!$this->hasEffect(Effect::WATER_BREATHING) && !$this->isInsideOfWater()) {
            $hasUpdate = true;
            $airTicks = $this->getDataProperty(self::DATA_AIR) - $tickDiff;
            if ($airTicks <= -20) {
                $airTicks = 0;
                $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
                $this->attack($ev->getFinalDamage(), $ev);
            }
            $this->setDataProperty(Entity::DATA_AIR, Entity::DATA_TYPE_SHORT, $airTicks);
        } else {
            $this->setDataProperty(Entity::DATA_AIR, Entity::DATA_TYPE_SHORT, 300);
        }

        Timings::$timerEntityBaseTick->stopTiming();
        return $hasUpdate;
    }

    public function onUpdate($currentTick) {
        if (!$this->isAlive()) {
            if (++$this->deadTicks >= 23) {
                $this->close();
                return false;
            }
            return true;
        }

        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
        $this->entityBaseTick($tickDiff);

        $target = $this->updateMove($tickDiff);
        if ($target instanceof Player) {
            if ($this->distance($target) <= 2) {
                $this->pitch = 22;
                $this->x = $this->lastX;
                $this->y = $this->lastY;
                $this->z = $this->lastZ;
            }
        } elseif (
            $target instanceof Vector3
            && $this->distance($target) <= 1
        ) {
            $this->moveTime = 0;
        }
        return true;
    }

}