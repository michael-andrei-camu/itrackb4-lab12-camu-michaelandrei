<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_creation_form_is_editable_and_saves_a_book(): void
    {
        $genre = Genre::create(['name' => 'Fiction']);

        $this->get(route('books.create'))
            ->assertOk()
            ->assertSee('name="code"', false)
            ->assertSee('name="title"', false)
            ->assertSee('name="author"', false)
            ->assertSee('name="genre_id"', false)
            ->assertSee('name="price"', false)
            ->assertSee('Save Book');

        $response = $this->post(route('books.store'), [
            'code' => 1001,
            'title' => 'The Test Book',
            'author' => 'Test Author',
            'genre_id' => $genre->id,
            'price' => '12.50',
        ]);

        $book = Book::where('title', 'The Test Book')->firstOrFail();

        $response->assertRedirect(route('books.details', $book->id));
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'code' => 1001,
            'author' => 'Test Author',
            'genre' => 'Fiction',
            'genre_id' => $genre->id,
            'price' => '12.50',
        ]);
        $this->get(route('books.catalog'))
            ->assertOk()
            ->assertSee('The Test Book');
        $this->get(route('books.genre', 'fiction'))
            ->assertOk()
            ->assertSee('The Test Book');
    }

    public function test_book_creation_validates_required_fields(): void
    {
        $this->from(route('books.create'))
            ->post(route('books.store'), [])
            ->assertRedirect(route('books.create'))
            ->assertSessionHasErrors(['code', 'title', 'author', 'genre_id', 'price']);
    }

    public function test_book_can_be_edited_and_updated(): void
    {
        $originalGenre = Genre::create(['name' => 'Fiction']);
        $updatedGenre = Genre::create(['name' => 'Fantasy']);

        $book = Book::create([
            'code' => 2001,
            'title' => 'Original Title',
            'author' => 'Original Author',
            'genre' => $originalGenre->name,
            'genre_id' => $originalGenre->id,
            'price' => '10.00',
        ]);

        $this->get(route('books.edit', $book))
            ->assertOk()
            ->assertSee('action="'.route('books.update', $book).'"', false)
            ->assertSee('Old Filter Redirect')
            ->assertSee('value="2001"', false)
            ->assertSee('value="Original Title"', false)
            ->assertSee('value="Original Author"', false)
            ->assertSee('value="10.00"', false)
            ->assertSee('value="'.$originalGenre->id.'" selected', false);

        $response = $this->put(route('books.update', $book), [
            'code' => 2002,
            'title' => 'Updated Title',
            'author' => 'Updated Author',
            'genre_id' => $updatedGenre->id,
            'price' => '15.50',
        ]);

        $response->assertRedirect(route('books.catalog'));
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'code' => 2002,
            'title' => 'Updated Title',
            'author' => 'Updated Author',
            'genre' => 'Fantasy',
            'genre_id' => $updatedGenre->id,
            'price' => '15.50',
        ]);

        $this->get(route('books.details', $book))
            ->assertOk()
            ->assertSee('Updated Title')
            ->assertSee('Updated Author');
    }

    public function test_invalid_book_update_redirects_to_edit_and_preserves_old_input(): void
    {
        $genre = Genre::create(['name' => 'Fiction']);
        $book = Book::create([
            'code' => 3001,
            'title' => 'Original Title',
            'author' => 'Original Author',
            'genre' => $genre->name,
            'genre_id' => $genre->id,
            'price' => '10.00',
        ]);

        $response = $this->put(route('books.update', $book), [
            'code' => 3002,
            'title' => 'Draft Title',
            'author' => 'Draft Author',
            'genre_id' => $genre->id,
            'price' => 'not-a-price',
        ]);

        $response->assertRedirect(route('books.edit', $book));
        $response->assertSessionHasErrors(['price']);
        $response->assertSessionHas('_old_input', [
            'code' => 3002,
            'title' => 'Draft Title',
            'author' => 'Draft Author',
            'genre_id' => $genre->id,
            'price' => 'not-a-price',
        ]);

        $this->get(route('books.edit', $book))
            ->assertOk()
            ->assertSee('value="3002"', false)
            ->assertSee('value="Draft Title"', false)
            ->assertSee('value="Draft Author"', false)
            ->assertSee('value="not-a-price"', false)
            ->assertSee('value="'.$genre->id.'" selected', false)
            ->assertSee('The price field must be a number.');
    }

    public function test_old_filter_redirect_button_restores_values_without_saving(): void
    {
        $genre = Genre::create(['name' => 'Fiction']);
        $book = Book::create([
            'code' => 4001,
            'title' => 'Saved Title',
            'author' => 'Saved Author',
            'genre' => $genre->name,
            'genre_id' => $genre->id,
            'price' => '10.00',
        ]);

        $response = $this->put(route('books.update', $book), [
            '_action' => 'old-input-redirect',
            'code' => 'invalid-code',
            'title' => 'Unsaved Draft',
            'author' => 'Draft Author',
            'genre_id' => $genre->id,
            'price' => 'invalid-price',
        ]);

        $response->assertRedirect(route('books.edit', $book));
        $response->assertSessionHas('_old_input', [
            'code' => 'invalid-code',
            'title' => 'Unsaved Draft',
            'author' => 'Draft Author',
            'genre_id' => $genre->id,
            'price' => 'invalid-price',
        ]);
        $response->assertSessionHas('status', 'Form values restored. Changes were not saved.');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'code' => 4001,
            'title' => 'Saved Title',
            'author' => 'Saved Author',
        ]);

        $this->get(route('books.edit', $book))
            ->assertOk()
            ->assertSee('Old Filter Redirect')
            ->assertSee('value="Unsaved Draft"', false)
            ->assertSee('value="Draft Author"', false)
            ->assertSee('value="invalid-code"', false)
            ->assertSee('value="invalid-price"', false)
            ->assertSee('Form values restored. Changes were not saved.');
    }

    public function test_nonexistent_book_uses_custom_404_page(): void
    {
        $this->get('/books/99999')
            ->assertNotFound()
            ->assertSee('404 - Page Not Found')
            ->assertSee('The book or page you are looking for does not exist in our directory.')
            ->assertSee(route('books.catalog'));
    }
}
