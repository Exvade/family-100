<?php

namespace Tests\Feature;

use App\Livewire\AnswerForm;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnswerFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_answers_page_embeds_the_modal_and_no_longer_has_create_or_edit_pages(): void
    {
        $question = Question::factory()->create();

        $this->get(route('family-100.answers.index', $question))->assertOk()->assertSeeLivewire(AnswerForm::class);
        $this->get("/family-100/pertanyaan/{$question->id}/jawaban/create")->assertNotFound();
    }

    public function test_opening_for_a_new_answer_suggests_the_next_ranking(): void
    {
        $question = Question::factory()->create();
        $question->answers()->create(['answer' => 'A', 'ranking' => 4]);

        Livewire::test(AnswerForm::class, ['question' => $question])
            ->dispatch('open-answer-form', id: null)
            ->assertSet('answerId', null)
            ->assertSet('ranking', '5')
            ->assertDispatched('answer-form-ready');
    }

    public function test_an_answer_can_be_added(): void
    {
        $question = Question::factory()->create();

        Livewire::test(AnswerForm::class, ['question' => $question])
            ->dispatch('open-answer-form', id: null)
            ->set('answer', 'Piring')
            ->set('ranking', '2')
            ->call('save')
            ->assertRedirect(route('family-100.answers.index', $question));

        $this->assertDatabaseHas('answers', ['question_id' => $question->id, 'answer' => 'Piring', 'ranking' => 2]);
        $this->assertSame('Jawaban ditambahkan.', session('status'));
    }

    public function test_an_answer_can_be_edited(): void
    {
        $answer = Answer::factory()->create(['answer' => 'Lama', 'ranking' => 1]);

        Livewire::test(AnswerForm::class, ['question' => $answer->question])
            ->dispatch('open-answer-form', id: $answer->id)
            ->assertSet('answer', 'Lama')
            ->assertSet('ranking', '1')
            ->set('answer', 'Baru')
            ->set('ranking', '3')
            ->call('save')
            ->assertRedirect(route('family-100.answers.index', $answer->question_id));

        $this->assertSame('Baru', $answer->fresh()->answer);
        $this->assertSame(3, $answer->fresh()->ranking);
        $this->assertSame('Jawaban diperbarui.', session('status'));
    }

    public function test_input_is_validated(): void
    {
        $question = Question::factory()->create();

        Livewire::test(AnswerForm::class, ['question' => $question])
            ->dispatch('open-answer-form', id: null)
            ->set('answer', '')
            ->set('ranking', '0')
            ->call('save')
            ->assertHasErrors(['answer' => 'required', 'ranking' => 'min'])
            ->set('ranking', 'abc')
            ->call('save')
            ->assertHasErrors(['ranking' => 'integer']);

        $this->assertDatabaseCount('answers', 0);
    }

    public function test_an_answer_of_another_question_cannot_be_opened_or_edited(): void
    {
        $question = Question::factory()->create();
        $foreign = Answer::factory()->create(['answer' => 'Milik lain']);

        Livewire::test(AnswerForm::class, ['question' => $question])
            ->dispatch('open-answer-form', id: $foreign->id)
            ->assertNotFound();
    }
}
