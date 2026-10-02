<?php

namespace App\Support;

/**
 * Choices for a list page's "Show N entries" control.
 */
final class PageSize
{
    public const OPTIONS = [10, 25, 50, 100];

    /** Page size used for "All". */
    public const ALL = 100000;
}
