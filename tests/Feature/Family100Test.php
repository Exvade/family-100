<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Family100Test extends TestCase
{
    use RefreshDatabase;

    public function test_question_can_be_created_and_redirects_to_its_answers(): void
    {
        $response = $this->post(route('family-100.questions.store'), [
            'question' => 'Sebutkan benda di dapur',
            'display_limit' => 5,
        ]);

        $question = Question::firstOrFail();
        $response->assertRedirect(route('family-100.answers.index', $question));
        $this->assertSame(5, $question->display_limit);
    }

    public function test_question_requires_valid_input(): void
    {
        $this->post(route('family-100.questions.store'), ['question' => '', 'display_limit' => 0])
            ->assertSessionHasErrors(['question', 'display_limit']);
    }

    public function test_many_answers_can_be_added_to_a_question(): void
    {
        $question = Question::factory()->create();

        foreach (['Piring' => 1, 'Sendok' => 2, 'Panci' => 3] as $name => $rank) {
            $this->post(route('family-100.answers.store', $question), ['answer' => $name, 'ranking' => $rank])
                ->assertRedirect(route('family-100.answers.index', $question));
        }

        $this->assertSame(['Piring', 'Sendok', 'Panci'], $question->answers->pluck('answer')->all());
        $this->get(route('family-100.answers.index', $question))->assertOk()->assertSee('Sendok');
    }

    public function test_answer_can_be_updated_and_deleted(): void
    {
        $answer = Answer::factory()->create(['answer' => 'Lama', 'ranking' => 1]);

        $this->put(route('family-100.answers.update', $answer), ['answer' => 'Baru', 'ranking' => 2])
            ->assertRedirect(route('family-100.answers.index', $answer->question_id));
        $this->assertSame('Baru', $answer->fresh()->answer);

        $this->delete(route('family-100.answers.destroy', $answer))
            ->assertRedirect(route('family-100.answers.index', $answer->question_id));
        $this->assertModelMissing($answer);
    }

    public function test_deleting_a_question_deletes_its_answers(): void
    {
        $answer = Answer::factory()->create();

        $this->delete(route('family-100.questions.destroy', $answer->question))->assertRedirect();

        $this->assertModelMissing($answer);
    }

    public function test_tv_shows_only_the_display_limit_of_answers_ordered_by_ranking(): void
    {
        $question = Question::factory()->create(['display_limit' => 2]);
        $question->answers()->create(['answer' => 'Ketiga', 'ranking' => 3]);
        $question->answers()->create(['answer' => 'Pertama', 'ranking' => 1]);
        $question->answers()->create(['answer' => 'Kedua', 'ranking' => 2]);

        $this->get(route('family-100.questions.tv', $question))
            ->assertOk()
            ->assertSeeInOrder(['Pertama', 'Kedua'])
            ->assertDontSee('Ketiga');
    }

    public function test_status_message_is_exposed_for_sweetalert(): void
    {
        $question = Question::factory()->create();

        $this->followingRedirects()
            ->delete(route('family-100.questions.destroy', $question))
            ->assertSee('data-flash="Pertanyaan dihapus."', false);
    }

    public function test_answered_toggle_marks_answer_and_tv_state_reflects_it(): void
    {
        $question = Question::factory()->create(['display_limit' => 2]);
        $first = $question->answers()->create(['answer' => 'Pertama', 'ranking' => 1]);
        $question->answers()->create(['answer' => 'Kedua', 'ranking' => 2]);

        $this->getJson(route('family-100.questions.tv.state', $question))->assertJsonPath('answered', []);

        $this->patchJson(route('family-100.answers.answered', $first))->assertOk()->assertJson(['answered' => true]);
        $this->getJson(route('family-100.questions.tv.state', $question))->assertJsonPath('answered', [$first->id]);

        $this->patchJson(route('family-100.answers.answered', $first))->assertOk()->assertJson(['answered' => false]);
        $this->getJson(route('family-100.questions.tv.state', $question))->assertJsonPath('answered', []);
    }

    public function test_answer_beyond_display_limit_cannot_be_marked_answered(): void
    {
        $question = Question::factory()->create(['display_limit' => 1]);
        $question->answers()->create(['answer' => 'Pertama', 'ranking' => 1]);
        $hidden = $question->answers()->create(['answer' => 'Kedua', 'ranking' => 2]);

        $this->patchJson(route('family-100.answers.answered', $hidden))->assertStatus(422);
        $this->assertFalse($hidden->fresh()->is_answered);
    }

    public function test_timer_uses_the_configured_duration_and_can_start_pause_and_reset(): void
    {
        $question = Question::factory()->create();

        $this->put(route('family-100.settings.update'), ['timer_duration' => 30])->assertRedirect();
        $this->assertSame(30, Setting::timerDuration());

        $this->getJson(route('family-100.questions.tv.state', $question))
            ->assertJsonPath('timer.status', 'idle')
            ->assertJsonPath('timer.remaining_ms', 30000);

        $this->postJson(route('family-100.questions.timer', [$question, 'start']))
            ->assertOk()->assertJsonPath('status', 'running');

        $this->travel(10)->seconds();
        $state = $this->postJson(route('family-100.questions.timer', [$question, 'pause']))
            ->assertOk()->assertJsonPath('status', 'paused')->json();
        $this->assertEqualsWithDelta(20000, $state['remaining_ms'], 50);

        $this->travel(60)->seconds();
        $this->getJson(route('family-100.questions.tv.state', $question))->assertJsonPath('timer.status', 'paused');

        $this->postJson(route('family-100.questions.timer', [$question, 'start']))->assertJsonPath('status', 'running');
        $this->travel(21)->seconds();
        $this->getJson(route('family-100.questions.tv.state', $question))
            ->assertJsonPath('timer.status', 'finished')
            ->assertJsonPath('timer.remaining_ms', 0);

        $this->postJson(route('family-100.questions.timer', [$question, 'reset']))
            ->assertJsonPath('status', 'idle')->assertJsonPath('remaining_ms', 30000);
    }

    public function test_timer_duration_setting_is_validated(): void
    {
        $this->get(route('family-100.settings.edit'))->assertOk()->assertSee('value="60"', false);

        $this->put(route('family-100.settings.update'), ['timer_duration' => 1])->assertSessionHasErrors('timer_duration');
        $this->assertSame(60, Setting::timerDuration());
    }

    public function test_unknown_timer_action_is_not_found(): void
    {
        $question = Question::factory()->create();

        $this->postJson(route('family-100.questions.timer', [$question, 'explode']))->assertNotFound();
    }

    public function test_wrong_button_increments_counter_exposed_to_tv(): void
    {
        $question = Question::factory()->create();

        $this->getJson(route('family-100.questions.tv.state', $question))->assertJsonPath('wrong_count', 0);

        $this->postJson(route('family-100.questions.wrong', $question))->assertOk()->assertJsonPath('wrong_count', 1);

        $this->travel(6)->seconds();
        $this->postJson(route('family-100.questions.wrong', $question))->assertJsonPath('wrong_count', 2);

        $this->getJson(route('family-100.questions.tv.state', $question))->assertJsonPath('wrong_count', 2);
    }

    public function test_wrong_button_is_throttled_to_once_every_five_seconds(): void
    {
        $question = Question::factory()->create();
        $other = Question::factory()->create();

        $this->postJson(route('family-100.questions.wrong', $question))->assertOk();

        $this->travel(3)->seconds();
        $this->postJson(route('family-100.questions.wrong', $question))->assertStatus(429);
        $this->assertSame(1, $question->fresh()->wrong_count);

        // Pertanyaan lain tidak ikut terkunci.
        $this->postJson(route('family-100.questions.wrong', $other))->assertOk();

        $this->travel(3)->seconds();
        $this->postJson(route('family-100.questions.wrong', $question))->assertOk();
        $this->assertSame(2, $question->fresh()->wrong_count);
    }

    public function test_pages_render(): void
    {
        $question = Question::factory()->hasAnswers(2)->create();
        $answer = $question->answers()->first();

        foreach ([
            route('family-100.questions.index'),
            route('family-100.questions.create'),
            route('family-100.questions.edit', $question),
            route('family-100.answers.index', $question),
            route('family-100.answers.create', $question),
            route('family-100.answers.edit', $answer),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
