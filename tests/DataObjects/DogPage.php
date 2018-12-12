<?php

namespace BatchWrite\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

/**
 * Class DogPage
 * @package BatchWrite\Tests
 */
class DogPage extends SiteTree implements TestOnly
{
    /**
     * @var array
     */
    private static $db = array(
        'Author' => 'Varchar',
    );
}
