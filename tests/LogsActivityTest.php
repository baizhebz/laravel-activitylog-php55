<?php

namespace Spatie\Activitylog\Test;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Traits\LogsActivity;

class LogsActivityTest extends TestCase
{
    /** @var \Spatie\Activitylog\Test\Article|\Spatie\Activitylog\Traits\LogsActivity */
    protected $article;

    public function setUp()
    {
        parent::setUp();
//
//        $this->article = new class() extends Article {
//            use LogsActivity;
//
//            use SoftDeletes;
//        };

        $this->assertCount(0, Activity::all());
    }

    /** @test */
    public function it_will_log_the_creation_of_the_model()
    {
        $article = $this->createArticle();
        $this->assertCount(1, Activity::all());

        $this->assertInstanceOf(get_class($article), $this->getLastActivity()->subject);
        $this->assertEquals($article->id, $this->getLastActivity()->subject->id);
        $this->assertEquals('created', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_log_an_update_of_the_model()
    {
        $article = $this->createArticle();

        $article->name = 'changed name';
        $article->save();

        $this->assertCount(2, Activity::all());

        $this->assertInstanceOf(get_class($article), $this->getLastActivity()->subject);
        $this->assertEquals($article->id, $this->getLastActivity()->subject->id);
        $this->assertEquals('updated', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_log_the_deletion_of_a_model_without_softdeletes()
    {
//        $articleClass = new class() extends Article {
//            use LogsActivity;
//        };

        $article = new TempArticleB();

        $article->save();

        $this->assertEquals('created', $this->getLastActivity()->description);

        $article->delete();

        $this->assertEquals('deleted', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_log_the_deletion_of_a_model_with_softdeletes()
    {
        $article = $this->createArticle();

        $article->delete();

        $this->assertCount(2, Activity::all());

        $this->assertEquals(get_class($article), $this->getLastActivity()->subject_type);
        $this->assertEquals($article->id, $this->getLastActivity()->subject_id);
        $this->assertEquals('deleted', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_log_the_restoring_of_a_model_with_softdeletes()
    {
        $article = $this->createArticle();

        $article->delete();

        $article->restore();

        $this->assertCount(3, Activity::all());

        $this->assertEquals(get_class($article), $this->getLastActivity()->subject_type);
        $this->assertEquals($article->id, $this->getLastActivity()->subject_id);
        $this->assertEquals('restored', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_can_fetch_all_activity_for_a_model()
    {
        $article = $this->createArticle();

        $article->name = 'changed name';
        $article->save();

        $activities = $article->activity;

        $this->assertCount(2, $activities);
    }

    /** @test */
    public function it_can_fetch_soft_deleted_models()
    {
        $this->app['config']->set('laravel-activitylog.subject_returns_soft_deleted_models', true);

        $article = $this->createArticle();

        $article->name = 'changed name';
        $article->save();

        $article->delete();

        $activities = $article->activity;

        $this->assertCount(3, $activities);

        $this->assertEquals(get_class($article), $this->getLastActivity()->subject_type);
        $this->assertEquals($article->id, $this->getLastActivity()->subject_id);
        $this->assertEquals('deleted', $this->getLastActivity()->description);
        $this->assertEquals('changed name', $this->getLastActivity()->subject->name);
    }

    /** @test */
    public function it_can_log_activity_to_log_named_in_the_model()
    {
//        $articleClass = new class() extends Article {
//            use LogsActivity;
//
//            public function getLogNameToUse()
//            {
//                return 'custom_log';
//            }
//        };

        $article = new TempArticleD();
        $article->name = 'my name';
        $article->save();

        $this->assertEquals($article->id, Activity::inLog('custom_log')->first()->subject->id);
        $this->assertCount(1, Activity::inLog('custom_log')->get());
    }

    /** @test */
    public function it_will_not_log_an_update_of_the_model_if_only_ignored_attributes_are_changed()
    {
//        $articleClass = new class() extends Article {
//            use LogsActivity;
//
//            protected static $ignoreChangedAttributes = ['text'];
//        };

        $article = new TempArticleE();
        $article->name = 'my name';
        $article->save();

        $article->text = 'ignore me';
        $article->save();

        $this->assertCount(1, Activity::all());

        $this->assertInstanceOf(get_class($article), $this->getLastActivity()->subject);
        $this->assertEquals($article->id, $this->getLastActivity()->subject->id);
        $this->assertEquals('created', $this->getLastActivity()->description);
    }

    /**
     * @return \Spatie\Activitylog\Test\Models\Article
     */
    protected function createArticle()
    {
        $article = new TempArticleC();
        $article->name = 'my name';
        $article->save();

        return $article;
    }
}

class TempArticleC extends Article {
    use LogsActivity;

    use SoftDeletes;
};

class TempArticleD extends Article {
    use LogsActivity;

    public function getLogNameToUse()
    {
        return 'custom_log';
    }
};

class TempArticleE extends Article {
    use LogsActivity;

    protected static $ignoreChangedAttributes = ['text'];
};