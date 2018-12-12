<?php

namespace BatchWrite\Tests;

use BatchWrite\Helpers\Batch;
use SilverStripe\Dev\SapphireTest;

/**
 * Class BatchWriteManyManyTest
 * @package BatchWrite\Tests
 */
/**
 * Class BatchWriteManyManyTest
 * @package BatchWrite\Tests
 */
class BatchWriteManyManyTest extends SapphireTest
{
    /**
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * @var array
     */
    protected static $extra_dataobjects = array(
        Animal::class,
        Batman::class,
        Cat::class,
        Child::class,
        Child::class,
        Dog::class,
        DogPage::class,
        Human::class,
    );

    /**
     * BatchWriteManyManyTest constructor.
     */
    public function __construct()
    {
        $this->setUpBeforeClass();
    }

    /**
     *
     */
    public function testWriteManyMany_CreateParentAndChildren_WritesManyMany()
    {
        $parent = new Batman();
        $parent->Name = 'Bruce Wayne';
        $parent->Car = 'Bat mobile';

        $children = array();
        for ($i = 0; $i < 5; $i++) {
            $child = new Child();
            $child->Name = 'Soldier #' . $i;
            $children[] = $child;
        }

        $batch = new Batch();

        $batch->write(array($parent));
        $batch->write($children);

        $sets = array();
        foreach ($children as $child) {
            $sets[] = array($parent, 'Children', $child);
        }
        $batch->writeManyMany($sets);

        $parent = Human::get()->first();
        $this->assertEquals(5, $parent->Children()->Count());
    }
}
