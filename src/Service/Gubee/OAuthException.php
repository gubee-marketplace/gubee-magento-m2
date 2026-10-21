<?php

declare(strict_types=1);

namespace Gubee\Integration\Service\Gubee;

use RuntimeException;

/** An OAuth error safe to display without transport details or credentials. */
class OAuthException extends RuntimeException
{
}
