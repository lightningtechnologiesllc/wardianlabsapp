<?php

declare(strict_types=1);

namespace App\Core\Messaging;

use App\Core\Messaging\Message;

/**
 * An event is a message representing reactions to other messages (for example commands).
 */
abstract class Event extends Message {}
