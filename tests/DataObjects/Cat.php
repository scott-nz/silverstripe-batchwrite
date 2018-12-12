<?php

namespace BatchWrite\Tests;

use SilverStripe\Dev\TestOnly;

/**
 * Class Cat
 * @package BatchWrite\Tests
 */
class Cat extends Animal implements TestOnly
{
    /**
     * @var bool
     */
    private $onBeforeWriteCalled = false;

    /**
     * @var bool
     */
    private $onAfterWriteCalled = false;

    /**
     * @var array
     */
    private static $db = array(
        'Type' => 'Varchar',
        'HasClaws' => 'Boolean',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'Enemy' => Dog::class,
    );

    /**
     *
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->onBeforeWriteCalled = true;
    }

    /**
     *
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $this->onAfterWriteCalled = true;
    }

    /**
     * @return bool
     */
    public function getOnBeforeWriteCalled()
    {
        return $this->onBeforeWriteCalled;
    }

    /**
     * @return bool
     */
    public function getOnAfterWriteCalled()
    {
        return $this->onAfterWriteCalled;
    }
}
