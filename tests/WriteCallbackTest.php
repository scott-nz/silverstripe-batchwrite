<?php

namespace BatchWrite\Tests;

use SilverStripe\Dev\SapphireTest;

/**
 * Class WriteCallbackTest
 * @package BatchWrite\Tests
 */
/**
 * Class WriteCallbackTest
 * @package BatchWrite\Tests
 */
class WriteCallbackTest extends SapphireTest
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
     * WriteCallbackTest constructor.
     */
    public function __construct()
    {
        $this->setUpBeforeClass();
    }

    /**
     * @throws \ValidationException
     * @throws null
     */
    public function testCallback_SetOnAfterWriteCallback_CallbackCalled()
    {
        $dog = new Dog();
        $dog->Name = 'Jim bob';

        $owner = new Human();
        $owner->Name = 'Hilly Stewart';

        $owner->onAfterWriteCallback(function ($owner) use ($dog) {
            $dog->OwnerID = $owner->ID;
            $dog->write();
        });

        $owner->write();

        $this->assertTrue($owner->exists());
        $this->assertTrue($dog->exists());
        $this->assertEquals(1, Human::get()->Count());
        $this->assertEquals(1, Dog::get()->Count());
        $this->assertEquals($owner->ID, $dog->OwnerID);
    }

    /**
     * @throws \ValidationException
     * @throws null
     */
    public function testCallback_SetOnBeforeWriteCallback_CallbackCalled()
    {
        $dog = new Dog();
        $dog->Name = 'Jim bob';

        $owner = new Human();
        $owner->Name = 'Hilly Stewart';
        $owner->write();

        $dog->onBeforeWriteCallback(function ($dog) use ($owner) {
            $dog->OwnerID = $owner->ID;
        });

        $dog->write();

        $this->assertTrue($owner->exists());
        $this->assertTrue($dog->exists());
        $this->assertEquals(1, Human::get()->Count());
        $this->assertEquals(1, Dog::get()->Count());
        $this->assertEquals($owner->ID, $dog->OwnerID);
    }

    /**
     * @throws \ValidationException
     * @throws null
     */
    public function testCallback_SetOnAfterExistsCallback_CallbackCalled()
    {
        $dog1 = new Dog();
        $dog1->Name = 'Jim bob';

        $dog2 = new Dog();
        $dog2->Name = 'Super Dog';

        $owner = new Human();
        $owner->Name = 'Hilly Stewart';
        $owner->write();

        $owner->onAfterExistsCallback(function ($owner) use ($dog1)  {
            $dog1->OwnerID = $owner->ID;
            $dog1->write();
        });

        $owner->write();

        $owner->onAfterExistsCallback(function ($owner) use ($dog2)  {
            $dog2->OwnerID = $owner->ID;
            $dog2->write();
        });

        $this->assertTrue($owner->exists());
        $this->assertTrue($dog1->exists());
        $this->assertTrue($dog2->exists());
        $this->assertEquals(1, Human::get()->Count());
        $this->assertEquals(2, Dog::get()->Count());
        $this->assertEquals($owner->ID, $dog1->OwnerID);
        $this->assertEquals($owner->ID, $dog2->OwnerID);
    }
}
