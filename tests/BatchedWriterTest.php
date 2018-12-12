<?php

namespace BatchWrite\Tests;

use BatchWrite\Helpers\BatchedWriter;
use BatchWrite\Helpers\OnAfterExists;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;

/**
 * Class BatchedWriterTest
 * @package BatchWrite\Tests
 */
/**
 * Class BatchedWriterTest
 * @package BatchWrite\Tests
 */
class BatchedWriterTest extends SapphireTest
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
     * BatchedWriterTest constructor.
     */
    public function __construct()
    {
        $this->setUpBeforeClass();
    }

    /**
     *
     */
    public function testWrite_WriteObjects_ObjectsWritten()
    {
        $batchSizes = array(10, 30, 100, 300);

        foreach ($batchSizes as $size) {

            $owners = array();
            $dogs = array();
            $cats = array();

            $writer = new BatchedWriter($size);

            for ($i = 0; $i < 100; $i++) {
                $owner = new Human();
                $owner->Name = 'Human ' . $i;

                $dog = new Dog();
                $dog->Name = 'Dog ' . $i;

                $cat = new Cat();
                $cat->Name = 'Cat ' . $i;

                $owner->onAfterExistsCallback(function ($owner) use ($dog, $writer) {
                    $dog->OwnerID = $owner->ID;
                    $writer->write($dog);
                });

                $dog->onAfterExistsCallback(function ($dog) use ($cat, $writer) {
                    $cat->EnemyID = $dog->ID;
                    $writer->write($cat);
                });

                $owners[] = $owner;
                $dogs[] = $dog;
                $cats[] = $cat;

            }

            // writes dogs first time
            $writer->write($dogs);

            // dogs written again from owner callback
            $writer->write($owners);

            $writer->finish();

            $owners = Human::get();
            $dogs = Dog::get();
            $cats = Cat::get();

            $this->assertEquals(100, $owners->Count());
            $this->assertEquals(100, $dogs->Count());
            $this->assertEquals(100, $cats->Count());

            for ($i = 0; $i < 100; $i++) {
                $owner = $owners[$i];
                $dog = $dogs[$i];
                $cat = $cats[$i];

                $this->assertEquals($owner->ID, $dog->OwnerID);
                $this->assertEquals($dog->ID, $cat->EnemyID);
            }

            $writer->delete($owners);
            $writer->delete($dogs);
            $writer->delete($cats);
            $writer->finish();

            $this->assertEquals(0, Human::get()->Count());
            $this->assertEquals(0, Dog::get()->Count());
            $this->assertEquals(0, Cat::get()->Count());
        }
    }

    /**
     *
     */
    public function testWriteManyMany_SetChildrenForParent_RelationWritten()
    {
        $parent = new Human();
        $parent->Name = 'Bob';

        $children = array();
        for ($i = 0; $i < 5; $i++) {
            $child = new Child();
            $child->Name = 'Soldier #' . $i;
            $children[] = $child;
        }

        $writer = new BatchedWriter();

        $afterExists = new OnAfterExists(function () use($writer, $parent, $children) {
            $writer->writeManyMany($parent, 'Children', $children);
        });

        $afterExists->addCondition($parent);
        $afterExists->addCondition($children);

        $writer->write(array($parent));
        $writer->write($children);
        $writer->finish();

        $parent = Human::get()->first();
        $this->assertEquals(5, $parent->Children()->Count());
    }

    /**
     *
     */
    public function testWriteToStages_ManyPages_WritesObjectsToStage()
    {
        $sizes = array(10, 30, 100, 300);

        foreach ($sizes as $size) {
            $writer = new BatchedWriter($size);

            $pages = array();
            for ($i = 0; $i < 100; $i++) {
                $page = new DogPage();
                $page->Title = 'Wonder Pup  '. $i;
                $pages[] = $page;
            }

            $writer->writeToStage($pages, 'Stage');
            $writer->finish();

            $currentStage = Versioned::get_stage();
            Versioned::set_reading_mode('Stage');
            $this->assertEquals(100, DogPage::get()->Count());

            Versioned::set_reading_mode('Live');
            $this->assertEquals(0, DogPage::get()->Count());

            $writer->writeToStage($pages, 'Live');
            $writer->finish();

            Versioned::set_reading_mode('Live');
            $this->assertEquals(100, DogPage::get()->Count());

            Versioned::set_reading_mode($currentStage);

            $writer->deleteFromStage($pages, 'Stage', 'Live');
            $writer->finish();

            $this->assertEquals(0, DogPage::get()->Count());
        }
    }
}
