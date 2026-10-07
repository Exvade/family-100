<?php

namespace Tests\Feature;

use App\Livewire\AnswerList;
use App\Livewire\QuestionList;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class MobileListTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_embed_the_mobile_list_components(): void
    {
        $question = Question::factory()->create();

        $this->get(route('family-100.questions.index'))->assertOk()->assertSeeLivewire(QuestionList::class);
        $this->get(route('family-100.answers.index', $question))->assertOk()->assertSeeLivewire(AnswerList::class);
    }

    public function test_questions_load_in_batches_as_the_user_scrolls(): void
    {
        Question::factory()->count(20)->create();

        Livewire::test(QuestionList::class)
            ->assertViewHas('questions', fn ($q) => $q->count() === 8)
            ->assertViewHas('hasMore', true)
            ->call('loadMore')
            ->assertViewHas('questions', fn ($q) => $q->count() === 16)
            ->call('loadMore')
            ->assertViewHas('questions', fn ($q) => $q->count() === 20)
            ->assertViewHas('hasMore', false);
    }

    public function test_numbers_follow_the_default_order_even_when_sorted_or_searched(): void
    {
        $a = Question::factory()->create(['question' => 'Alpha']);
        $b = Question::factory()->create(['question' => 'Bravo']);

        Livewire::test(QuestionList::class)
            ->set('sort', 'question:desc')
            ->assertViewHas('questions', fn ($q) => $q->pluck('id')->all() === [$b->id, $a->id])
            ->assertViewHas('numbers', fn ($n) => $n[$a->id] === 0 && $n[$b->id] === 1);
    }

    public function test_search_filters_and_resets_the_loaded_count(): void
    {
        Question::factory()->count(12)->create(['question' => 'Umum']);
        Question::factory()->create(['question' => 'Kondangan 100%']);

        Livewire::test(QuestionList::class)
            ->call('loadMore')
            ->set('search', 'kondangan')
            ->assertSet('loaded', 8)
            ->assertViewHas('total', 1)
            ->set('search', '%')
            ->assertViewHas('total', 1)
            ->set('search', '_')
            ->assertViewHas('total', 0);
    }

    public function test_sort_value_is_validated_against_the_allowed_columns(): void
    {
        Question::factory()->count(2)->create();

        Livewire::test(QuestionList::class)
            ->set('sort', 'id; drop table questions:sideways')
            ->assertOk()
            ->assertViewHas('total', 2);
    }

    public function test_answers_are_listed_by_ranking_and_flag_those_outside_the_tv_limit(): void
    {
        $question = Question::factory()->create(['display_limit' => 2]);
        $third = $question->answers()->create(['answer' => 'C', 'ranking' => 3]);
        $first = $question->answers()->create(['answer' => 'A', 'ranking' => 1]);
        $second = $question->answers()->create(['answer' => 'B', 'ranking' => 2]);
        Answer::factory()->create(['question_id' => Question::factory()->create()->id]);

        Livewire::test(AnswerList::class, ['question' => $question])
            ->assertViewHas('answers', fn ($a) => $a->pluck('id')->all() === [$first->id, $second->id, $third->id])
            ->assertViewHas('onTvIds', fn ($ids) => $ids->all() === [$first->id, $second->id])
            ->set('sort', 'no:desc')
            ->assertViewHas('answers', fn ($a) => $a->first()->id === $third->id)
            ->set('sort', 'name:desc')
            ->assertViewHas('answers', fn ($a) => $a->first()->answer === 'C')
            ->set('search', 'b')
            ->assertViewHas('total', 1);
    }

    public function test_the_question_id_of_the_answer_list_cannot_be_changed_from_the_browser(): void
    {
        $question = Question::factory()->create();

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(AnswerList::class, ['question' => $question])->set('questionId', 999);
    }

    public function test_participant_list_can_put_winners_first(): void
    {
        $a = \App\Models\Participant::factory()->create(['name' => 'A']);
        $b = \App\Models\Participant::factory()->create(['name' => 'B', 'won_at' => now()]);
        $c = \App\Models\Participant::factory()->create(['name' => 'C']);

        Livewire::test(\App\Livewire\ParticipantList::class)
            ->set('sort', 'status:desc')
            ->assertViewHas('participants', fn ($p) => $p->pluck('id')->all() === [$b->id, $a->id, $c->id])
            ->assertSee('PEMENANG')
            ->set('sort', 'status:asc')
            ->assertViewHas('participants', fn ($p) => $p->pluck('id')->all() === [$a->id, $c->id, $b->id]);
    }
}
